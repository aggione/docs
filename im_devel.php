<?php
/*	
	https://dotnetu.local/ldap/im.html 
	https://dotnetu.local/ldap/im_devel.html 
	https://iam.local.santannapisa.it
	percorso: /docker/iam-docker/apache/www/iam
*/

/*
	TO DO:
		- introdurre lo stato di ripristino account scaduto 
			in questo stato deve essere possibile inserire o modificare l'account ma contrariamente allo stato di bozza non deve essere cancellabile 
			(valutare la creazione di un campo che dice se è cancellabile e riportare lo stato in bozza)

	perché 	  &eacute; 
	è		  		&egrave;    
*/
session_cache_limiter('private');
session_cache_expire(0);
session_start();
date_default_timezone_set('Europe/Rome');

// $nf=strtoupper(__FILE__);
// $anf=explode('/',$nf);
// $eanf=end($anf);
// $anf=explode('.',$eanf);
// 'IAM'=$anf[0];
$app='IAM';
// define('app', $anf[0]);
// $app=strtoupper($anf[0]);

// --------------------------------------------------------------
$devel=false;
// $devel=true;	// @@@@@ (LDAP di prova e AD sul branch di prova)

if (empty($_SESSION['IAM']['devel'])){$_SESSION['IAM']['devel']=0;}
$sviluppo_tab=false;
$sviluppo_albo=false;
@set_time_limit(3600);	//  60 minuti (secondi esecuzione di una chiamata)	- @set_time_limit(0); // tempo illimitato

require_once('comune.php');
$ip=getIpAddress();
$logf=checklog($app);
require_once('fpdf.php');

set_error_handler("alboError");

$tempo_inattivo_massimo=round(30*60);	// in secondi (30 minuti)
// $tempo_minimo_tra_login=round(5);	// in secondi (5 secondi)
$ora=round(microtime(true));			// tempo attuale in secondi

if (!isset($_SESSION['IAM']['ultima_attivita'])) {
	$_SESSION['IAM']['ultima_attivita']=$ora;
	tolog('sessione iniziata - id: '.session_id ().'ora: '.date("Y-m-d H:i:s",$ora));
	if (isset($_REQUEST['func'])){
		if ($_REQUEST['func'] != 'get_home'){
			// echo base64_encode('<script type="text/javascript" temp="1">location.reload(true); $("[temp]").remove();</script>');
			echo base64_encode('<script type="text/javascript" temp="1">
				header("Refresh:3"); 
				$("[temp]").remove();
			</script>');
			// get_home();		// ripristina i dati iniziali
		}
	}
}
if (($_SESSION['IAM']['ultima_attivita']+$tempo_inattivo_massimo) < $ora){
	// tempo di sessione scaduto per inattività
	tolog('sessione scaduta - ora: '.date("Y-m-d H:i:s",$ora). ' - ultima attività: '.date("Y-m-d H:i:s",$_SESSION['IAM']['ultima_attivita']));
	session_unset(); 
	session_destroy(); 	
	session_start();
	if (empty($_SESSION['IAM']['devel'])){$_SESSION['IAM']['devel']=0;}
	$_SESSION['IAM']['ultima_attivita']=$ora;
	tolog('sessione re-iniziata - id: '.session_id ().'ora: '.date("Y-m-d H:i:s",$ora));
	echo base64_encode('<div class="alert alert-danger">Sessione scaduta</div>');
	if (isset($_REQUEST['func'])){
		if ($_REQUEST['func'] != 'get_home'){
			sleep(3);
			// echo base64_encode('<script type="text/javascript">location.reload(true);</script>');
			echo base64_encode('<script type="text/javascript">header("Refresh:3");</script>');
			// get_home();		// ripristina i dati iniziali
		}
	}
} else {
	$_SESSION['IAM']['ultima_attivita']=$ora;
//	if ($devel){tolog('OK nel tempo di sessione');}
}

// @set_time_limit(1800);			//  30 minuti (periodo massimo di esecuzione di una chiamata)

$develerr=3;
if (isset($_SESSION['IAM']['develerr'])) {$develerr=$_SESSION['IAM']['develerr'];};
switch ($develerr) {
	case 0:		error_reporting(0);											break;	// Turn off all error reporting
	case 1:		error_reporting(E_ERROR | E_WARNING | E_PARSE);				break;	// Report simple running errors
	case 2:		error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);	break;	// Reporting E_NOTICE can be good too (to report uninitialized variables or catch variable name misspellings ...)
	case 3:		error_reporting(E_ALL ^ E_NOTICE);							break;	// Report all errors except E_NOTICE This is the default value set in php.ini
	case 4:		error_reporting(E_ALL);										break;	// Report all PHP errors (see changelog)
	case 5:		error_reporting(-1);										break;	// Report all PHP errors
	default:	error_reporting(E_ERROR | E_WARNING | E_PARSE);				break;	// Report simple running errors
};

// globali --- date e orari
$dt=date("Y-m-d H:i:s");		// 	data e ora classica 		es:	2017-11-06 10:49:00
$dtoR=date("YmdHis");			//	data e ora rovesciata		es:	20171106104900
$dtR=date("Ymd");				//	data rovesciata					es:	20171106
$dtit=date("d/m/Y H:i:s");		// 	data e ora in italiano	es:	06/11/2017 10:49:00
$gdm=date("d"); 				// 	giorno del mese (1-31)
$gds=date("N"); 				// 	giorno della settimana (1-7) 1=lunedì
$odg=date("G"); 				// 	ora del giorno (0-23)
/*
// --- uso di strtotime
echo('ora: '.date("d/m/Y H:i:s",strtotime("now"))."<br>");
echo('3 ott 2005: '.date("d/m/Y H:i:s",strtotime("3 October 2005"))."<br>");
echo('tra 5 ore: '.date("d/m/Y H:i:s",strtotime("+5 hours"))."<br>");
echo('tra una settimana: '.date("d/m/Y H:i:s",strtotime("+1 week"))."<br>");
echo('tra 10 giorni 7 ore e 5 secondi: '.date("d/m/Y H:i:s",strtotime("+1 week 3 days 7 hours 5 seconds"))."<br>");
echo('prossimo lunedì: '.date("d/m/Y H:i:s",strtotime("next Monday"))."<br>");
echo('ultima domenica: '.date("d/m/Y H:i:s",strtotime("last Sunday"))."<br>");
echo('una settimana fa: '.date("d/m/Y H:i:s",strtotime("-1 week"))."<br>");
*/

$req='REQUEST';
foreach ($_REQUEST as $key => $value) {	// _REQUEST contiene _GET o _POST
	unset($_SESSION['IAM'][$key]);
	$_SESSION['IAM'][$key]=$value;
	$$key = $value;
	if (is_array($value)){$v=json_encode($value);} else {$v=$value;}
	$req.=" (".$key.' => '.$v.")";
};
tolog($req);	// registro la richiesta
// if ($sviluppo_albo){tolog_albo('request: '.json_encode($_REQUEST));}

$do=true;
$doerr='';
// --- sanitizza gli input passati
// $nostr=['drop ','truncate ','\\','select ','databases ','*','tables ','views ','sinonyms ','union ','insert ','delete ',' and ',' or ',' not ','||'];
$nostr=['drop ','truncate ','\\','databases ','tables ','views ','sinonyms ','insert ','delete ','update ']; // di SELECT ne ho bisogno in quanto testo le query delle ML automatiche
foreach ($_REQUEST as $key => $value) {	// _REQUEST contiene _GET o _POST
	if (is_array($value)){$v=json_encode($value);} else {$v=$value;}
	for ($i = 0; $i < count($nostr); $i++) {
		if (stripos($v,$nostr[$i])){
			$do=0;
			$doerr.=$key.': '.$v.' -> con '.$nostr[$i]."\r\n";
		}
	}
};
if ($doerr != ''){
	echo base64_encode(get_alert('<strong>ERRORE AB_001</strong><br />Contatta i servizi ICT per ulteriori spiegazioni, grazie.','danger'));
	tolog('SANITIZE: '.$doerr);
	return;
}

$ip_ldap = '192.168.64.11';
$ip_ldap_devel = '192.168.64.6';

$ip_ad = '192.168.64.81';	// 192.168.64.18 ???
// $dn="CN=Bongiorni Alberto,OU=Servizi ICT,OU=OpenLdap,DC=sssapisa,DC=it"
$ou_ad="OU=OpenLdap,DC=sssapisa,DC=it";
// $dn="CN=Bongiorni Alberto,OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it";	
$ou_ad_devel="OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it";

if ($devel) {$ip_ldap=$ip_ldap_devel; $ou_ad=$ou_ad_devel;}

$ldap_conn = ldap_connect($ip_ldap,389);
// $ldap_conn = ldap_connect('ldaps://'.$ip_ldap,636);
if ($ldap_conn) {
	ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
} else {
	echo base64_encode($s=get_alert('<strong>Non puoi usare questo software</strong><br />Contatta i servizi ICT per ulteriori spiegazioni, grazie.','danger'));
	tolog('ERRORE CONNESSIONE LDAP');
	return;
}
$ldapBind = ldap_bind($ldap_conn,'cn=manager,o=sss,c=it','fugasse');
if (!$ldapBind) {
	echo base64_encode(get_alert('Credenziali di autenticazione (LDAP) errate','danger'));
	return;
};

$ad_conn = ldap_connect($ip_ad,389);
// $ad_conn = ldap_connect('ldaps://'.$ip_ad.':636');
// $ad_conn = ldap_connect('ldaps://'.$ip_ad.':4444');
if ($ad_conn) {
	ldap_set_option($ad_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ad_conn, LDAP_OPT_REFERRALS, 0);
} else {
	echo base64_encode(get_alert('<strong>Non puoi usare questo software</strong><br />Contatta i servizi ICT per ulteriori spiegazioni, grazie.','danger'));
	tolog('ERRORE CONNESSIONE AD');
	return;
}
$adBind = ldap_bind($ad_conn,'sssapisa.it/OpenLdap/Servizi ICT/User Read','readuser');	// ok utente valido anche prova
// ramo prova: sssapisa.it/ADprova/
if (!$adBind) {
	echo base64_encode(get_alert('Credenziali di autenticazione (AD) errate','danger'));
	return;
};

// $uu='SSS_IMPORT'; $pp='ugovimport'; $cc='SSS-DB'; $conn_old=getconn($uu,$pp,$cc,'o');
$uu='C##SSS_IMPORT'; $pp='ugovimport'; $cc='ORAXEWIN10'; $conn_new=getconn($uu,$pp,$cc,'o');
if (empty($conn_new)) {
	echo base64_encode(get_alert('problemi di connessione al db di interscambio .','danger'));
	trigger_error('non ho la connessione a XE', E_USER_ERROR);
	return;
}

$sql = "ALTER SESSION SET NLS_DATE_FORMAT = 'DD/MM/YYYY HH24:MI:SS'";
$p = oci_parse($conn_new, $sql);
$e = oci_execute($p);

// $uu='mailadm'; $pp='mailpwd'; $cc='idb2_mail'; 
// $conn_idb2=getconn($uu,$pp,$cc,'m');

// Storico LDAP
$uu='storico_ldap'; $pp='Ld4pSt0r1c0'; $cc='mysql'; 
$conn_sl = mysqli_connect($cc, $uu, $pp);	
// if (!empty(mysqli_error($conn_sl))){$s.=get_alert(mysqli_error($conn_sl),'danger');}
mysqli_select_db($conn_sl,'storico_ldap'); //or die('Could not select database');
// if (!empty(mysqli_error($conn_sl))){$s.=get_alert(mysqli_error($conn_sl),'danger');}

// Storico LDAP ori
$uu='storico_ldap'; $pp='Ld4pSt0r1c0'; $cc='dotnetu.local'; 
$conn_slo = mysqli_connect($cc, $uu, $pp);	
// if (!empty(mysqli_error($conn_sl))){$s.=get_alert(mysqli_error($conn_sl),'danger');}
mysqli_select_db($conn_slo,'storico_ldap'); //or die('Could not select database');
// if (!empty(mysqli_error($conn_sl))){$s.=get_alert(mysqli_error($conn_sl),'danger');}

get_dizionari();
if (empty($_REQUEST['func'])){$_REQUEST['func']='get_home';}	
get_do();

// ----------------------------------------------------------------------------
// FUNZIONI CLASSICHE DEL FRAMEWORK AB (personalizzate)
// ----------------------------------------------------------------------------
function get_do(){
	global $sviluppo_albo, $conn_new, $conn_sl, $devel;
	if (!empty($_SESSION['IAM']['uid_login']) or $_REQUEST['func'] == 'get_home'){
		$s='';
		// if ($_REQUEST['func']!='getpbp'){$_SESSION['IAM']['percento']=0;}
		if (!$conn_new) {
			echo base64_encode(get_alert('problemi di connessione al db di interscambio ..','danger'));
			$e = oci_error();
			trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
			die;
		}
		if ($_REQUEST['func']=='up'){
			if (!empty($_REQUEST['sact'])){
				if ($_REQUEST['sact']=='adc'){$_REQUEST['func']='cfla';}
				if ($_REQUEST['sact']=='alu'){$_REQUEST['func']='cfla_alumni';}
			}
		}
		switch ($_REQUEST['func']) {
			// case 'getpbp':
				// if (empty($_SESSION['IAM']['percento'])){$_SESSION['IAM']['percento']=0;}
				// echo $_SESSION['IAM']['percento'];
				// if (!empty(ob_get_level())){flush(); ob_flush();}
				// break;
		 // ----------------------------------------------------------------------------
			case 'get_home':	// inizio
		 // ----------------------------------------------------------------------------
				$s=get_home();
				break;
		 // ----------------------------------------------------------------------------
			case 'ga':			// controlla l'esistenza del token di autenticazione
		 // ----------------------------------------------------------------------------
				if (!empty($_SESSION['IAM']['uid_login']) and !empty($_SESSION['IAM']['ultima_attivita']) and (!empty($_SERVER['AJP_eppn']) or !empty($_SERVER['HTTP_EPPN']))){$s=$_SESSION['IAM']['uid_login'].' - '.$_SESSION['IAM']['ultima_attivita'];}
				// @@@@@ inserire il controllo degli account da creare per elaborarli
				break;
		 // ----------------------------------------------------------------------------
			case 'reloadtab':	// 
		 // ----------------------------------------------------------------------------
				if (!empty($_REQUEST['tab'])){$s=get_tab($tab);} else {$s='';}
				break;
			// ----------------------------------------------------------------------------
			case 'get_can_d':	// dati carriere attive
			// ----------------------------------------------------------------------------
				$s=get_can_d();
				break;
			// ----------------------------------------------------------------------------
			case 'impers':		// dati carriere attive
			// ----------------------------------------------------------------------------
				$s=impers();
				break;
			// ----------------------------------------------------------------------------
			// account
			// ----------------------------------------------------------------------------
			case 'cfla':			// carica file lista account
				$s=cfla();
				break;
			case 'cfla_alumni':		// carica file lista account alumni
				$s=cfla_alumni();
				break;
			case 'get_ra':			// richiesta account
				$s=get_ra();
				break;
			case 'ra_stato':
				$s=get_ra_stato();	// rilettura richieste account pendenti o intero storico
				break;
			case 'ra_get_old':
				$s.=ricrea_storico();
				// $s.=get_ra_stato(); // alla fine della ricostruzione dello storico visualizzo come all'inizio
				break;
			case 'n_acc':	// nuovo account
				$s.=get_n_acc();
				break;
			case 'get_uid_m':	// riporta i dati selezionati nella form di caricamento
				$a=array();
				$atr=array("'"=>"","-"=>"");	// in nome e cognome elimino accenti e trattini
				$inext=0; $a['domi'][$inext]['domf']='nome'; $a['domi'][$inext]['domc']=ucwords(strtolower(strtr($_REQUEST['nome'],$atr)));
				$inext++; $a['domi'][$inext]['domf']='cognome'; $a['domi'][$inext]['domc']=ucwords(strtolower(strtr($_REQUEST['cognome'],$atr)));
				$inext++; $a['domi'][$inext]['domf']='email_ex'; $a['domi'][$inext]['domc']=$_REQUEST['mail'];
				$inext++; $a['domi'][$inext]['domf']='cf'; $a['domi'][$inext]['domc']=trim($_REQUEST['cf']);
				$inext++; $a['domi'][$inext]['domf']='ra_inquadramento'; $a['domi'][$inext]['domc']=$_REQUEST['ki'];
				if (empty($_REQUEST['ka']) and !empty($_REQUEST['ki'])){
					// se ho ki e non ho ka la posso cercare su ab_can 
					$sql="SELECT distinct ka FROM C##SSS_IMPORT.AB_CAN WHERE ki ='".$_REQUEST['ki']."'";
					$b=load_db($conn_new,$sql,'o');
					if ($b[0]>0 and $b[1]>0){
						if (!empty($b[2]['KA'][0])){
							$inext++; $a['domi'][$inext]['domf']='ka'; $a['domi'][$inext]['domc']=$b[2]['KA'][0];
							$_REQUEST['ka']=$b[2]['KA'][0];
						}
					}
				}
				$inext++; $a['domi'][$inext]['domf']='ka'; $a['domi'][$inext]['domc']=$_REQUEST['ka'];
				if (!empty($_REQUEST['ka'])){
					// cerco il responsabile dell'afferenza se selezionata
					$sql="SELECT distinct a.nome, a.cognome, a.cod_fisc cf, a.ka, a.da FROM C##SSS_IMPORT.AB_CAN a ,(SELECT matricola,fl_responsabile FROM C##SSS_TABLES.V_IE_RU_ORGANICO_COP WHERE TRUNC (dt_inizio_cop) <= TRUNC (SYSDATE) AND TRUNC (dt_fine_cop) >= TRUNC (SYSDATE) and fl_responsabile=1) o WHERE a.k = o.matricola and a.ka='".$_REQUEST['ka']."'";
					// $inext++; $a['domi'][$inext]['domf']='note'; $a['domi'][$inext]['domc']=$sql;
					$b=load_db($conn_new,$sql,'o');
					if ($b[0]>0 and $b[1]>0){
						if (!empty($b[2]['CF'][0])){
							$inext++; $a['domi'][$inext]['domf']='docint'; $a['domi'][$inext]['domc']=$b[2]['CF'][0];
						}
					}
				}
				$inext++; $a['domi'][$inext]['domf']='kr'; $a['domi'][$inext]['domc']=$_REQUEST['kr'];
				$inext++; $a['domi'][$inext]['domf']='di'; $a['domi'][$inext]['domc']=date("d/m/Y");
				$inext++; $a['domi'][$inext]['domf']='df'; $a['domi'][$inext]['domc']=$_REQUEST['df'];
				$s.=json_encode($a);
				break;
			case 'get_e_ra':
				$s.=get_e_ra();
				break;
			// case 'pn_acc':	// prepara nuovo account
				// $s=get_prepara_nuovo_account();
				// break;
			// case 'sn_acc':	// salva nuovo account
				// $s=get_salva_nuovo_account();
				// break;
			case 'imp_old':	// visualizza il VECCHIO repository degli account registrati in LDAP
				$s=get_imp_old();
				break;
			case 'cerca_na':
				$s=get_anomalie_ab_can_ldap();
				break;
			// case 'ldap_accenti_case':
				// $s=sistema_ldap_accenti_case();
				// break;
			case 'dett_ldap':
				$s=dett_ldap_ad($_REQUEST['k'],'LDAP');
				break;
			case 'dett_ad':
				$s=dett_ldap_ad($_REQUEST['k'],'AD');
				break;
			case 'dett_can':
				$r=array('tit' => 'Dettaglio carriera attiva', 'msg' => get_can_d($_REQUEST['uid']), 'stl' => 'bg-success text-center text-white');
				$s=json_encode($r);		
				break;
			case 'dett_storico':
				// $r=array('tit' => 'Dettaglio storico LDAP', 'msg' => get_storico_ldap($_REQUEST['uid']), 'stl' => 'bg-success text-center text-white');
				// $s=json_encode($r);		
				$s=get_storico_ldap($_REQUEST['uid']);
				break;
			// ----------------------------------------------------------------------------
			// ad ldap
			// ----------------------------------------------------------------------------
			case 'm_acc':	// modifica account
				$s=get_modifica_account();
				break;
			case 'm_fac':	// modifica account
				$s=get_cerca_account();
				break;
			case 'mod-acc':
				$s=get_mod_account();
				break;
			case 'sm_acc':	// salva modifica account
				$s=get_salva_modifica_account();
				break;
			// ----------------------------------------------------------------------------
			// password
			// ----------------------------------------------------------------------------
			case 'gp':	// password
				$s=get_cambia_password();
				break;
			case 'sgp':	// salva password
				$s=get_salva_modifica_password();
				break;
			case 'cpd':	// cerca password disallineate
				$s=get_password_disallineate();
				break;
			// ----------------------------------------------------------------------------
			// eccezioni
			// ----------------------------------------------------------------------------
			case 'cf_calcola':	// nuova eccezione
				$calc = new CalcolaCF();
				// eczn_nome,eczn_cognome,eczn_genere,eczn_dtn,eczn_comune
				$s=get_alert('CF calcolato: ' . $calc->calcola($_REQUEST['eczn_nome'], $_REQUEST['eczn_cognome'], $_REQUEST['eczn_genere'], new \DateTime(convert_date($_REQUEST['eczn_dtn'])), $_REQUEST['eczn_comune']),'warning');
				break;
			case 'ne':	// nuova eccezione
				$s=get_nuova_eccezione();
				break;
			case 'sge':	// salva eccezione
				$s=get_salva_nuova_eccezione();
				break;
			case 'me':	// modifica eccezione
				$s=get_lista_eccezioni();
				break;
			case 'de':	// delete eccezione
				$s=get_delete_eccezioni();
				break;
			case 'cf_to_input':	// 
				$s=get_genere_nascita_comune();
				break;
			// ----------------------------------------------------------------------------
			// foto
			// ----------------------------------------------------------------------------
			case 'grc_foto':
				$s=get_table_richieste_foto();
				break;
			case 'cn_foto':
				$s=get_form_upload_foto();
				break;
			case 'rr_foto':
				$s=get_risposta_richiesta_foto();
				break;
			case 'set_upload_foto':
				$s=set_upload_foto();
				break;
			case 'rfm':
				$s=get_rfm_foto();
				break;
			case 'rfe':
				$s=get_rfe_foto();
			
			// ----------------------------------------------------------------------------
			// ML
			// ----------------------------------------------------------------------------
			case 'dea_ml':
				$s=get_del_automatismo_ML();
				break;			
			case 'nuova_lista_ml':
				$s=get_nuova_lista_ML();
				break;
			case 'sort_fix_ml':
				$s=get_sort_fix_ML();
				break;
			case 'clearnull_ml':
				$s=get_clearnull_ML();
				break;
			case 'dett_ml':	// dettaglio ML
				$s=get_dettaglio_ML();
				break;
			case 'tq_ml': // test query ML
				$s=get_query_ML();
				break;
			case 'listmode_ml':
				$s=get_mode_ML();
				break;
			// case 'asu_ml':
				// $s=get_new_to_subscribe_ML();
				// break;
			// case 'nsu_ml':
				// $s=get_subscribe_ML();
				// break;
			// case 'unsub_ml':
				// $s=get_unsubscribe_ML();
				// break;
			// case 'ns_ml':
				// $s=get_ns_ML();
				// break;
			case 'lista_ml':
				$s=get_lista_ML();
				break;
			// case 'agg_ml':
				// $s=get_vl_ML();
				// break;
			// case 'agg_all_ml':
				// $s=get_vl_all_ML();
				// break;
			case 'cra_ml':
				$s=get_salva_automatismo_ML();
//				$r=array(
//					'tit' => 'Crea automatismo ML'
//					, 'msg' => json_encode($_REQUEST)
//					, 'domf' => 'ml_bottom'
//					, 'domc' => get_dettaglio_ML();
//				);
//				$s=json_encode($r);			
				break;
				// echo shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABCancellaLista '.$lista);
				// echo shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABNuovaLista '.$lista.' '.$owner);
				// echo shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABList '.$lista.' '.$azione.' '.$mail); $azione obbligatorio ['subscribe','unsubscribe','null','feed']
			case 'sau_ml':
				$s=get_salva_automatismo_ML();
				break;
			// ----------------------------------------------------------------------------
			// permessi ugov
			// ----------------------------------------------------------------------------
			case 'f_pu':
				$s=get_find_pu();
				break;
			case 'visute_pu':
				$s=visute_pu();
				break;
			case 'visruo_pu':
				$s=visruo_pu();
				break;
			case 'agg_pu':
				$s=agg_pu();
				break;
			// ----------------------------------------------------------------------------
			// attivita
			// ----------------------------------------------------------------------------
			case 'li_att':
				$s=get_attivita();
				break;
			case 'tg_att':
				$s=get_toggle_attivita();
				break;
			case 'agg_att':
				$s=aggiorna_attivita();
				break;
			// ----------------------------------------------------------------------------
			// acl
			// ----------------------------------------------------------------------------
			case 'e_acl':
				$s=get_leggi_acl();
				break;
			case 's_acl':
				$s=get_salva_acl();
				break;		
			// ----------------------------------------------------------------------------
			// ict
			// ----------------------------------------------------------------------------
			case 'ict_cerca':
				switch ($_REQUEST['ict_azione']) {
					case 'adt': 	$s=get_ict_adt();					break;	//Allievi da trasferire
					case 'ude': 	$s=get_ict_ude();					break;	//Utenti da eliminare
					case 'at': 		$s=get_agg_tabelle();				break;	//Aggiornamento tabelle
					case 'llw': 	$s=get_log_import();				break;	//Log last Week
					case 'lle': 	$s=get_log_import('A10_LOG_LAST');	break;	//Log last execution
					// case 'acmgf': 	$s=AllineaCMG(1);					break;	//Aggiorna CMG_BKP Full
					// case 'acmg': 	$s=AllineaCMG();					break;	//Aggiorna CMG_BKP
					case 'al': 		$s=get_anomalie_ldap();				break;	//Anomalie LDAP
					case 'anaal': 	$s=get_anomalie_ab_can_ldap();		break;	//Anagagrafiche non agganciate a LDAP
					case 'rng':   	$s=get_ruoli_non_gestiti();			break;	//Ruoli non in AB_RUOLO_NEW
					case "san":		$s=get_stesso_anno_nascita();		break;	//Stesso anno di nascita
					case "ancald":	$s=anomalie_caratteri_ldap();		break;
				}
				break;
			case 'anomalie_dett':
				$s=get_anomalie_dett();
				break;
		}
		// if ($_REQUEST['func']!='getpbp'){
			// $_SESSION['IAM']['percento']=100;
			echo base64_encode($s);
		// } else {
			// echo $_SESSION['IAM']['percento'];
		// }
	} else {
		tolog('Sessione scaduta');
		// $_SESSION['IAM']['percento']=100;
		echo base64_encode('<div class="alert alert-danger">Sessione scaduta</div><script type="text/javascript">header("Refresh:3");</script>');
		// header("Refresh:0");	header("Refresh:seconds;url=page");	// window.location.reload(true);
		// echo base64_encode('<script type="text/javascript">location.reload(true);</script>');
	}
}
function impers() {
	global $conn_new, $sviluppo_albo;
	$s='';
	if (isset($_REQUEST['conferma'])){
		// esegui impersonificazione
		$sql="select COGNOME, NOME, GENERE, LDAP_UID, MAIL, COD_FISC from c##sss_import.ab_can where LDAP_UID='".$_REQUEST['uid_impers']."'";	
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$_SESSION['IAM']['myDU']=$a[2];
			$_SESSION['IAM']['uid_login']=$_REQUEST['uid_impers'];
			$_SESSION['IAM']['my_acl']=$_SESSION['IAM']['je'][$_REQUEST['uid_impers']];
// if ($sviluppo_albo){write_log('_tmp.log',"impers - acl: ".json_encode($_SESSION['IAM']['my_acl']));}
		}
		$tit='<h2>impersonificazione confermata</h2>';
		$msg='La impersonificazione di '.$_SESSION['IAM']['myDU']['NOME'][0].' '.$_SESSION['IAM']['myDU']['COGNOME'][0];
		$msg.=' &egrave; stata eseguita !';
		$stl='bg-success text-center text-white';
		$btn='';
		// $domf='benvenuto'; // 
		// $domc='Benvenut'.(($_SESSION['IAM']['myDU']['GENERE'][0]=='M')?'o':'a').' <strong>'.$_SESSION['IAM']['myDU']['NOME'][0].' '.$_SESSION['IAM']['myDU']['COGNOME'][0].'</strong>'; // 
		$domf='contenuto'; // 
		$domc=get_home(); // 
	} else {
		$tit='<h2>impersonifica</h2>';
		$msg='';

		if (!empty($_SESSION['IAM']['ab_can'])){
			$ss='';
			for ($i=0; $i < count($_SESSION['IAM']['ab_can']['LDAP_UID']); $i++) {
				if ($_SESSION['IAM']['ab_can']['LDAP_UID'][$i] != '' and in_array($_SESSION['IAM']['ab_can']['LDAP_UID'][$i],$_SESSION['IAM']['jek'])){
					$ss.='<option data-tokens="'.$_SESSION['IAM']['ab_can']['LDAP_UID'][$i].'" value="'.$_SESSION['IAM']['ab_can']['LDAP_UID'][$i].'">'.$_SESSION['IAM']['ab_can']['COGNOME'][$i].' '.$_SESSION['IAM']['ab_can']['NOME'][$i].'</option>';
				}
			}
			$msg.='<select name="uid_impers" id="uid_impers" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep >
				<option data-tokens="0" value="0"></option>
				'.$ss.'
			</select>';
			// if ($_SESSION['IAM']['uid_login'] == 'a.bongiorni'){
				// if($_SESSION['IAM']['devel']==1){
					// $_SESSION['IAM']['devel']=0;
				// } else {
					// $_SESSION['IAM']['devel']=1;
				// }
				// $msg.=get_alert('devel: '.$_SESSION['IAM']['devel']);
			// }
		}
		
		$stl='bg-primary text-center text-white';
		$btn='<button id="modal-ok" class="btn btn-success btn-block" data-dismiss="modal" act="impers" dom="mm" conferma="y" getf="uid_impers">Conferma impersonificazione</button><button id="modal-ko" class="btn btn-dark btn-block" data-dismiss="modal">Annulla</button>';
		$domf='';
		$domc='';
	}
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn, 'domf' => $domf, 'domc' => $domc);
	$s=json_encode($r);			
	return $s;
	
}
function get_home(){
	global $conn_new, $devel, $ip_ldap, $ip_ad, $sviluppo_albo;
	$s='';
	if (!isset($_SESSION['IAM']['uid_shibboleth'])){ 
		if (isset($_SESSION['IAM']['uid_login'])){unset($_SESSION['IAM']['uid_login']);}
		if (isset($_SERVER['AJP_eppn'])) {	// shibboleth 
			$arr = explode("@", $_SERVER['AJP_eppn'], 2);
			$_SESSION['IAM']['uid_shibboleth'] = $arr[0];
		} else if (isset($_SERVER['HTTP_EPPN'])) {	// 
			$arr = explode("@", $_SERVER['HTTP_EPPN'], 2);
			$_SESSION['IAM']['uid_shibboleth'] = $arr[0];
		} else if (isset($_SERVER['REMOTE_USER'])) {	// 
			$arr = explode("@", $_SERVER['REMOTE_USER'], 2);
			$_SESSION['IAM']['uid_shibboleth'] = $arr[0];
		} else if (isset($_SERVER['PHP_AUTH_USER'])) {	// 
			$arr = explode("@", $_SERVER['PHP_AUTH_USER'], 2);
			$_SESSION['IAM']['uid_shibboleth'] = $arr[0];
		}
		$_SESSION['IAM']['uid_login']=$_SESSION['IAM']['uid_shibboleth'];
	}
	$j=file_get_contents('im_acl.json');
	$_SESSION['IAM']['je']=json_decode($j,true);
	$manutenzione=$_SESSION['IAM']['je']['manutenzione'];
	$_SESSION['IAM']['jek']=array_keys($_SESSION['IAM']['je']);
	if (isset($_SESSION['IAM']['myDU'])){unset($_SESSION['IAM']['myDU']);}
	if (!empty($_SESSION['IAM']['uid_login'])){if (in_array($_SESSION['IAM']['uid_login'],$_SESSION['IAM']['jek'])){
		$sql="select COGNOME, NOME, GENERE, LDAP_UID, MAIL, COD_FISC from c##sss_import.ab_can where LDAP_UID='".$_SESSION['IAM']['uid_login']."'";	
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$_SESSION['IAM']['myDU']=$a[2];
		}
	}}
	if (!isset($_SESSION['IAM']['myDU'])) {
		$s.=get_alert('<strong>Non puoi usare questo software</strong><br />Contatta i servizi ICT per ulteriori spiegazioni, grazie.','danger');
		if (isset($sql)){
			tolog('ERRORE lettura ab_can - sql: '.$sql);
		}
		return $s;
	}

	$_SESSION['IAM']['my_acl']=$_SESSION['IAM']['je'][$_SESSION['IAM']['uid_login']];
	// $s.=get_alert(json_encode($_SESSION['IAM']['my_acl']),'info');
	// $_SESSION['IAM']['all_acl']=$_SESSION['IAM']['je']['ALL'];
	$_SESSION['IAM']['tabs_acl']=$_SESSION['IAM']['je']['TABS'];

	$s.='<div class="row alert-warning">';
		$s.='<div class="col-sm-2 text-center"><img class="logo img-fluid img-thumbnail" src="logo.png" /></div>';
		$s.='<div class="col-sm-4 text-center my-auto"><h2><span class="d-none d-sm-inline text-danger">Identity Access Management</span></h2>';
		if ($devel) {$s.='<strong>DEVEL</strong> (LDAP: '.$ip_ldap.') (AD: '.$ip_ad.')';}
		$s.='</div>';
		if ($_SESSION['IAM']['uid_login']!=$_SESSION['IAM']['uid_shibboleth'] or stripos($_SESSION['IAM']['my_acl']['acl'],'S') !== false or stripos($_SESSION['IAM']['my_acl']['acl'],'I') !== false){
			$s.='<div class="col-sm-4 text-center my-auto" id="benvenuto">Benvenut'.(($_SESSION['IAM']['myDU']['GENERE'][0]=='M')?'o':'a').' <strong>'.$_SESSION['IAM']['myDU']['NOME'][0].' '.$_SESSION['IAM']['myDU']['COGNOME'][0].'</strong></div>';
			$s.='<div class="col-sm-2 text-center"><button class="btn btn-warning btn-sm btn-block" act="impers" dom="mm">Impersonifica</button></div>';
		} else {
			$s.='<div class="col-sm-6 text-center my-auto" id="benvenuto">Benvenut'.(($_SESSION['IAM']['myDU']['GENERE'][0]=='M')?'o':'a').' <strong>'.$_SESSION['IAM']['myDU']['NOME'][0].' '.$_SESSION['IAM']['myDU']['COGNOME'][0].'</strong></div>';
		}
	$s.='</div>';	
	if ($manutenzione=='1' and strpos($_SESSION['IAM']['my_acl']['acl'],'S')!==false){
		$s.=get_alert('<h3 class="text-center">MANUTENZIONE</h3>','danger');
	}
	$s.='
	<nav class="navbar navbar-expand-md bg-dark navbar-dark sticky-top">
		<!-- <a class="navbar-brand" href="#">Navbar</a> -->
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="collapsibleNavbar">
			<ul class="navbar-nav">';
			// $f=['account','eccezioni','password','foto','mail_list','permessi_ugov','attivita'];
			// $fi=['user','user-clock','unlock-alt','portrait','envelope','user-tag','file-invoice'];	// https://fontawesome.com/icons?d=gallery&p=3&m=free
			$f=array_keys($_SESSION['IAM']['tabs_acl']);
			$fi=array_values($_SESSION['IAM']['tabs_acl']);
			for ($j=0; $j<count($f); $j++){	// cerco il type corrispondente
				if (!empty($_SESSION['IAM']['my_acl'][$f[$j]])){
				// if ($_SESSION['IAM']['my_acl'][$f[$j]] != '' or $_SESSION['IAM']['all_acl'][$f[$j]] != ''){
					$t=get_tab($f[$j]);
					if ($t!=''){
						$s.='<li class="nav-item"><a id="navlink_'.$j.'" divcont="'.$f[$j].'" class="nav-link" d="'.$f[$j].'"';
						if ($j==0) {$s.=' active';}
						$s.='" href="#"><i class="fas fa-'.$fi[$j].'"></i> - '.implode(' ',explode('_',$f[$j])).'</a></li>';
					}
				}
			}
			$s.='</ul>
		</div>
		<h3 id="navbar-text" class="navbar-text text-white text-right"><i class="fas fa-'.$fi[0].'"></i> - '.$f[0].'</h3>
	</nav>';

	for ($j=0; $j<count($f); $j++){	// cerco il type corrispondente
		if (!empty($_SESSION['IAM']['my_acl'][$f[$j]])){
		// if ($_SESSION['IAM']['my_acl'][$f[$j]] != '' or $_SESSION['IAM']['all_acl'][$f[$j]] != ''){
			if ($manutenzione=='1' and strpos($_SESSION['IAM']['my_acl'][$f[$j]],'S')===false){
				$t='<br />'.get_alert('Attenzione, il sito &egrave; in manutenzione<br />per informazioni chiedi ad Alberto Bongiorni (3322)','warning');
			} else {
				$t='';
				$t.=get_tab($f[$j]);
			}
			if ($t!=''){
				$s.='<div class="container-fluid top-buffer contenuti';
				if ($j!=0) {$s.=' d-none';}
				$s.='" id="'.$f[$j].'"><div class="col-sm-12">'.$t.'</div></div>';
			}
		}
	}
	return $s;
}
function get_tab($nome){
	global $conf, $sviluppo_tab, $conn_new, $sviluppo_albo;

// if ($sviluppo_albo){write_log('_tmp.log',"get_tab - $nome");}
	
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}	// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}	// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}	// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}	// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict
	$s='';
	switch ($nome) {
		case 'account':	
if (true){		
			$peco=60;
			if ($sviluppo_tab and $peco<100 and $su){
				$s.='<div class="row alert alert-info">';
					$s.='<div class="col-sm-8">FUNZIONA: 
					<br />MANCA:
					</div>';
					$s.='<div class="col-sm-3">';
						$s.='<div class="progress-bar bg-'.($peco<80?($peco<33?'danger':'warning'):'success').'" style="width:'.$peco.'%;">'.$peco.'%</div>';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
					$s.='</div>';
				$s.='</div>';
			}
			$s.='<div class="row">';
				$s.='<div class="col-sm-2">';
					if ($su or $ict or $wr or $ut){
						$s.='<button class="btn btn-success btn-sm btn-block" act="n_acc" dom="ga_bottom" title="Nuovo account">Nuovo Account <i class="fa fa-id-card" aria-hidden="true"></i></button>';
					}
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					if ($su or $ict or $wr or $ut){
						$tit='Le tue richieste account';
						if ($su or $ict or $wr){$tit='Richieste account pendenti (cerca)';}
						$s.='<button id="get_ra_stato" class="btn btn-info btn-sm btn-block" act="ra_stato" dom="ga_bottom" getf="account_from,account_find">'.$tit.' <i class="fa fa-list-alt" aria-hidden="true"></i></button>';
					}
				$s.='</div>';
				$s.='<div class="col-sm-2">';
						$s.='<div class="row';
						if (!$su and !$ict){$s.=' d-none';}
						$s.='">';
							$s.='<div class="col-sm-5 text-right">';
								$s.='<label for="account_from">Dalla data:</label>';
							$s.='</div>';
							$s.='<div class="col-sm-7">';
								$s.=' <input type="text" name="account_from" id="account_from" class="form-control form-control-sm" placeholder="visualizza dalla data inizio" bta="get_ra_stato" value="'.date("d/m/Y",strtotime("-1 week")).'" dto />';
								$_REQUEST['account_from']=date("d/m/Y",strtotime("-1 week"));
							$s.='</div>';
						$s.='</div>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					if ($su or $ict){
						$s.=' <input type="text" name="account_find" id="account_find" class="form-control form-control-sm" placeholder="cerca (cf, uid, nome cognome)" bta="get_ra_stato" />';
					}
				$s.='</div>';
				$s.='<div class="col-sm-1">';
					if ($su){	// @@@@ disabilitare quando è in produzione
						$s.='<button title="visualizza il VECCHIO repository degli account registrati in LDAP (mysql)" class="btn btn-danger btn-sm btn-block" act="imp_old" dom="ga_bottom">Vecchio Storico</button>';
						$s.='<button title="ricrea il NUOVO repository degli account dal VECCHIO storico" class="btn btn-dark btn-sm btn-block" act="ra_get_old" dom="mm">Reload old</button>';
					}
				$s.='</div>';
				$s.='<div class="col-sm-1">';
					// $s.='<button class="btn btn-primary btn-sm btn-block" act="ldap_accenti_case" title="sistema lettere accentate e case di Nome e Cognome" dom="mm">sistema LDAP, accentate e case</button>';
					$s.='<a class="btn btn-success btn-sm btn-block" href="im_massivo_tipo.csv" title="scarica il csv tipo per il caricamento massivo">CSV tipo <i class="fa fa-download" aria-hidden="true"></i></a>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					if ($su or $ict or $wr or $ut){
						$s.='<button class="btn btn-info btn-sm btn-block" act="cfla" dom="mm" tipo="adc">Upload CSV account da creare <i class="fa fa-upload" aria-hidden="true"></i></button>';
					}
					if ($su){	// or $ict
						$s.='<button class="btn btn-warning btn-sm btn-block" act="cfla_alumni" dom="mm" tipo="alu">Upload CSV account alumni <i class="fa fa-upload" aria-hidden="true"></i></button>';
					}
				$s.='</div>';
			$s.='</div>';
			$s.='<div id="ga_bottom" class="py-2">';
// if ($sviluppo_albo){write_log('_tmp.log',"get_tab - prima di get_ra_stato");}
				$s.=get_ra_stato();	// visualizzazione iniziale delle richieste account da elaborare
// if ($sviluppo_albo){write_log('_tmp.log',"get_tab - dopo get_ra_stato");}
			$s.='</div>';
			break;
}			
		case 'ldap_ad':	
if (true){		
			$peco=60;
			if ($sviluppo_tab and $peco<100 and $su){
				$s.='<div class="row alert alert-info">';
					$s.='<div class="col-sm-8">FUNZIONA: la ricerca, salva modifiche su LDAP e AD (verificare)
					<br />MANCA:non sono sviluppate le ricerche di anomalie, non salva campi vuoti</div>';
					$s.='<div class="col-sm-3">';
						$s.='<div class="progress-bar bg-'.($peco<80?($peco<33?'danger':'warning'):'success').'" style="width:'.$peco.'%;">'.$peco.'%</div>';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
					$s.='</div>';
				$s.='</div>';
			}
			$s.='<form id="f_ma" class="">';
				$s.='<div class="row">';
					$s.='<div class="col-sm-1">';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
						if ($su){
							// $s.='<button class="btn btn-warning btn-sm btn-block" act="va" dom="la_bottom">Verifica</button>';
						}
					$s.='</div>';
					$s.='<div class="col-sm-3">';
						if ($ict or $su){
							$s.='<input type="text" name="find_account" id="find_account" class="form-control form-control-sm" placeholder="UID o Codice fiscale o Nome o Cognome - ricerca in LDAP (Users e GuestUsers)" bta="cerca_account_list" />';
						}
					$s.='</div>';
					$s.='<div class="col-sm-1">';
						if ($ict or $su){
							$s.='<button class="btn btn-primary btn-sm btn-block" act="m_acc" dom="la_bottom" tab="ma" id="cerca_account">Cerca</button>';
						}
					$s.='</div>';
					$s.='<div class="col-sm-1">';
						if ($ict or $su){
							$s.='<button class="btn btn-primary btn-sm btn-block" act="m_fac" dom="la_bottom" tab="ma" id="cerca_account_list">Cerca lista</button>';
						}
					$s.='</div>';
				$s.='</div>';
			$s.='</form>';
			$s.='<div id="la_bottom" class="py-2">';
			$s.='</div>';
			break;
}
		case 'eccezioni':	// 
if (true){		
			$peco=100;
			if ($sviluppo_tab and $peco<100 and $su){
				$s.='<div class="row alert alert-info">';
					$s.='<div class="col-sm-8">FUNZIONA: tutto
					<br />MANCA: nulla</div>';
					$s.='<div class="col-sm-4">';
						$s.='<div class="progress-bar bg-'.($peco<80?($peco<33?'danger':'warning'):'success').'" style="width:'.$peco.'%;">'.$peco.'%</div>';
					$s.='</div>';
				$s.='</div>';
			}
			$s.='<div class="row">';
				$s.='<div class="col-sm-2">';
					if ($ict or $su){
						$s.='<button class="btn btn-success btn-sm btn-block" act="ne" dom="eczn_bottom">Nuova eccezione</button>';
					}
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-success btn-sm btn-block" act="me" dom="eczn_bottom">Lista eccezioni</button>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-success btn-sm btn-block" act="me" dom="eczn_bottom" storico="y">Storico eccezioni</button>';
				$s.='</div>';
				$s.='<div class="col-sm-4">';
					$s.='<form id="f_fe" class="">';
						$s.='<input type="text" name="find_eccezione" id="find_eccezione" class="form-control form-control-sm" placeholder="UID o Codice fiscale o Nome o Cognome" bta="cerca_eccezioni" />';
					$s.='</form>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-primary btn-sm btn-block" act="me" dom="eczn_bottom" tab="fe" id="cerca_eccezioni">Cerca</button>';
				$s.='</div>';
			$s.='</div>';
			$s.='<div id="eczn_bottom"></div>';
			break;
}
		case 'password':	// 
if (true){		
			$peco=30;
			if ($sviluppo_tab and $peco<100 and $su){
				$s.='<div class="row alert alert-info">';
					$s.='<div class="col-sm-8">FUNZIONA: verifica
					<br />MANCA: forza e cambia password</div>';
					$s.='<div class="col-sm-4">';
						$s.='<div class="progress-bar bg-'.($peco<80?($peco<33?'danger':'warning'):'success').'" style="width:'.$peco.'%;">'.$peco.'%</div>';
					$s.='</div>';
				$s.='</div>';
			}
			$s.='<div class="row">';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-success btn-sm btn-block" act="gp" dom="gp_bottom" tipo="cp">Cambia password</button>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					if ($ict or $su){
						$s.='<button class="btn btn-success btn-sm btn-block" act="gp" dom="gp_bottom" tipo="fp">Forza password</button>';
					}
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-success btn-sm btn-block" act="gp" dom="gp_bottom" tipo="vp">Verifica password</button>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					if ($ict or $su){
						$s.='<button class="btn btn-warning btn-sm btn-block" act="cpd" dom="gp_bottom">Cerca password disallineate <strong><i class=\'text-success fas fa-clock\'></i></strong></button>';
					}
				$s.='</div>';
				$s.='<div class="col-sm-4">';
				$s.='</div>';
			$s.='</div>';
			$s.='<div id="gp_bottom"></div>';
			break;
}
		case 'foto':	// 
if (true){		
			$peco=60;
			if ($sviluppo_tab and $peco<100 and $su){
				$s.='<div class="row alert alert-info">';
					$s.='<div class="col-sm-8">FUNZIONA: Lettura foto e crop, salva nuova richiesta, verifica richieste pendenti, ricerca foto mancanti degli utenti attivi
					<br />MANCA: approva o rifiuta nuova richiesta, ricerca richieste precedenti, carica foto attuale in modifica</div>';
					$s.='<div class="col-sm-4">';
						$s.='<div class="progress-bar bg-'.($peco<80?($peco<33?'danger':'warning'):'success').'" style="width:'.$peco.'%;">'.$peco.'%</div>';
					$s.='</div>';
				$s.='</div>';
			}
			$s.='<div class="row">';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-success btn-sm btn-block" act="cn_foto" dom="gf_bottom">Carica nuova foto</button>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					if ($ict or $su){
						$s.='<button class="btn btn-success btn-sm btn-block" act="grc_foto" dom="gf_bottom">Gestisci richieste cambio foto</button>';
					}
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					if ($ict or $su){
						$s.='<button class="btn btn-success btn-sm btn-block" act="rfm" dom="gf_bottom">Ricerca foto mancanti</button>';
					}
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					if ($ict or $su){
						$s.='<button class="btn btn-success btn-sm btn-block" act="rfe" dom="gf_bottom">Ricerca foto esistenti</button>';
					}
				$s.='</div>';
				$s.='<div class="col-sm-4">';
				$s.='</div>';
			$s.='</div>';
			$s.='<div id="gf_bottom"></div>';
			break;
}
		case 'mail_list':	// 
if (true){		
			$peco=60;
			if ($sviluppo_tab and $peco<100 and $su){
				$s.='<div class="row alert alert-info">';
					$s.='<div class="col-sm-8">FUNZIONA: lista - visualizzazione e modifica automatismo e sottoscrizioni - prove query - lista null e feed - selezione nuove sottoscrizioni - aggiorna ML - Ricalcolo globale ML
					<br />MANCA: Inserimento nuova ML, cancellazione ML automatica, Cancella Automatismo</div>';
					$s.='<div class="col-sm-4">';
						$s.='<div class="progress-bar bg-'.($peco<80?($peco<33?'danger':'warning'):'success').'" style="width:'.$peco.'%;">'.$peco.'%</div>';
					$s.='</div>';
				$s.='</div>';
			}
			$s.='<div class="row">';
				$s.='<div class="col-sm-2">';
					if ($ict or $su){
						$s.='<button class="btn btn-success btn-sm btn-block" act="nuova_lista_ml" dom="mm">Nuova ML - lista distrib.</button>';
					}
				$s.='</div>';
				$s.='<div class="col-sm-1">';
					$s.='<button class="btn btn-success btn-sm btn-block" act="lista_ml" dom="ml_bottom">Lista ML</button>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					// $s.='<button class="btn btn-warning btn-sm btn-block" act="agg_all_ml" dom="ml_bottom">Aggiorna tutte ML</button>';
				$s.='</div>';

				$s.='<div class="col-sm-5">';
					$s.='<form id="f_ml" class="">';
						// $ml=shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABListLists ');
//						$ml=shell_exec('/usr/bin/perl AB.pl ABListLists ');
//						if (!empty($ml)){
//							$aml=json_decode($ml,true);
//							// $a=array('ML'=>$aml['ML']);
//							$sql="select * from c##sss_import.tbmailist where attivo=1 order by NOME_LISTA";	
//							$x=load_db($conn_new,$sql,'o');
//							if ($x[0]>0 and $x[1]>0) {	// almeno una riga e almeno una colonna
//								$tbmailist=$x[2];
//							}
//							$s.='<select name="nml" id="nml" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep ><option data-tokens="0" value="0"></option>';
//							for($i=0; $i<count($aml['ML']); $i++){
//								$css='primary';
//								if(!empty($tbmailist)){if (in_array($aml['ML'][$i],$tbmailist['NOME_LISTA'])){$css='success';}}
//								$s.='<option class="alert-'.$css.'" data-tokens="'.$aml['ML'][$i].'" value="'.$aml['ML'][$i].'"><span>'.$aml['ML'][$i].'</span></option>';
//							}
//							$s.='</select>';
//						} else {
							// $sql="select * from c##sss_import.tbmailist where attivo=1 order by NOME_LISTA";	
							$sql="select * from c##sss_import.tbmailist order by NOME_LISTA";	
							$x=load_db($conn_new,$sql,'o');
							if ($x[0]>0 and $x[1]>0) {	// almeno una riga e almeno una colonna
								$tbmailist=$x[2];
								$s.='<select name="nml" id="nml" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep ><option data-tokens="0" value="0"></option>';
								for($i=0; $i<count($tbmailist['NOME_LISTA']); $i++){
									$css='success';
									if ($tbmailist['ATTIVO'][$i]==0){$css='danger';}
									$s.='<option class="alert-'.$css.'" data-tokens="'.$tbmailist['NOME_LISTA'][$i].'" value="'.$tbmailist['NOME_LISTA'][$i].'"><span>'.$tbmailist['NOME_LISTA'][$i].'</span></option>';
								}
								$s.='</select>';
							} else {
								$s.=get_alert('impossibile leggere da communigate','danger');
							}
//						}
					$s.='</form>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-primary btn-sm btn-block" act="dett_ml" tab="ml" dom="ml_bottom">Dettaglio</button>';
				$s.='</div>';
			$s.='</div>';
			$s.='<div id="ml_bottom"></div>';
			break;
}
		case 'permessi_ugov':
if (true){		
			$peco=98;
			if ($sviluppo_tab and $peco<100 and $su){
				$s.='<div class="row alert alert-info">';
					$s.='<div class="col-sm-8">FUNZIONA: ricerca
					<br />MANCA: refresh lista primo e secondo utente dopo aggiornamento</div>';
					$s.='<div class="col-sm-4">';
						$s.='<div class="progress-bar bg-'.($peco<80?($peco<33?'danger':'warning'):'success').'" style="width:'.$peco.'%;">'.$peco.'%</div>';
					$s.='</div>';
				$s.='</div>';
			}
			$s.='<form id="f_pu">';
				$s.='<div class="row py-1">';
					$s.='<div class="col-sm-1 text-right">';
						$s.='<button class="btn btn-primary btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="g">Gruppi</button>';
					$s.='</div>';
					$s.='<div class="col-sm-1 text-right">';
						$s.='<button class="btn btn-primary btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="p">Profili</button>';
					$s.='</div>';
					$s.='<div class="col-sm-1 text-right">';
						$s.='<button class="btn btn-primary btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="c">Contesti</button>';
					$s.='</div>';
					$s.='<div class="col-sm-1 text-right">';
						$s.='<button class="btn btn-primary btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="u">Utenti</button>';
					$s.='</div>';
					$s.='<div class="col-sm-1 text-right">';
						$s.='<button class="btn btn-primary btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="a">Aree</button>';
					$s.='</div>';
					$s.='<div class="col-sm-1 text-right">';
						$s.='<button class="btn btn-primary btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="m">Moduli</button>';
					$s.='</div>';
					$s.='<div class="col-sm-1 text-right">';
						$s.='<button class="btn btn-primary btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="f">Funzioni</button>';
					$s.='</div>';
					$s.='<div class="col-sm-1 text-right">';
						$s.='<button class="btn btn-primary btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="r">Ruoli</button>';
					$s.='</div>';
					$s.='<div class="col-sm-2">';
						$s.='<span id="agg_pu"></span>';
					$s.='</div>';
					$s.='<div class="col-sm-2">';
						$s.='<button class="btn btn-warning btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="o">Organigramma</button>';
					$s.='</div>';
				$s.='</div>';
				$s.='<div class="row py-1">';
					$s.='<div class="col-sm-2 text-right">';
						$s.='cerca:';
					$s.='</div>';
					$s.='<div class="col-sm-6">';
						$s.='<input type="text" name="find_pu" id="find_pu" class="form-control form-control-sm" placeholder="cerca" />';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
						$s.='<button class="btn btn-primary btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="t">Cerca</button>';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
						// $s.='<button class="btn btn-danger btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="t" od="1" >Cerca</button>';
					$s.='</div>';
					$s.='<div class="col-sm-2">';
						$s.='<button class="btn btn-secondary btn-sm btn-block" act="agg_pu" getf="ugov1,ugov2">Aggiorna <i class="text-warning fas fa-clock"></i></button>';
					$s.='</div>';
				$s.='</div>';
				$s.='<div class="row py-1">';
					$s.='<div class="col-sm-2 text-right">';
						$s.='primo utente:';
					$s.='</div>';
					$s.='<div class="col-sm-6" id="col_ugov1">';
							$ss='';
							for ($j=0; $j < count($_SESSION['IAM']['utenti_ugov']['LDAP_UID']); $j++) {
								if ($_SESSION['IAM']['utenti_ugov']['LDAP_UID'][$j]!=''){
									$ss.='<option data-tokens="'.$_SESSION['IAM']['utenti_ugov']['LDAP_UID'][$j].'" value="'.$_SESSION['IAM']['utenti_ugov']['LDAP_UID'][$j].'">'.$_SESSION['IAM']['utenti_ugov']['COGNOME'][$j].' '.$_SESSION['IAM']['utenti_ugov']['NOME'][$j].'</option>';
								}
							}
							$s.='<select name="ugov1" id="ugov1" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep >
								<option data-tokens="0" value="0"></option>
								'.$ss.'
							</select>';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
						$s.='<button class="btn btn-primary btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="v">Visualizza</button>';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
						// $s.='<button class="btn btn-danger btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="v" od="1" >Visualizza</button>';
					$s.='</div>';
					$s.='<div class="col-sm-2">';
					$s.='</div>';
				$s.='</div>';
				$s.='<div class="row py-1">';
					$s.='<div class="col-sm-2 text-right">';
						$s.='secondo utente:';
					$s.='</div>';
					$s.='<div class="col-sm-6" id="col_ugov2">';
							$ss='';
							for ($j=0; $j < count($_SESSION['IAM']['utenti_ugov']['LDAP_UID']); $j++) {
								if ($_SESSION['IAM']['utenti_ugov']['LDAP_UID'][$j]!=''){
									$ss.='<option data-tokens="'.$_SESSION['IAM']['utenti_ugov']['LDAP_UID'][$j].'" value="'.$_SESSION['IAM']['utenti_ugov']['LDAP_UID'][$j].'">'.$_SESSION['IAM']['utenti_ugov']['COGNOME'][$j].' '.$_SESSION['IAM']['utenti_ugov']['NOME'][$j].'</option>';
								}
							}
							$s.='<select name="ugov2" id="ugov2" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep >
								<option data-tokens="0" value="0"></option>
								'.$ss.'
							</select>';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
						$s.='<button class="btn btn-primary btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="2">Compara</button>';
					$s.='</div>';
					$s.='<div class="col-sm-1">';
						// $s.='<button class="btn btn-danger btn-sm btn-block" act="f_pu" dom="pu_bottom" tab="pu" tipo="2" od="1" >Compara</button>';
					$s.='</div>';
				$s.='</div>';
			$s.='</form>';			
			$s.='<div id="pu_bottom"></div>';
			break;
}
		case 'attivita':
if (true){		
			$peco=100;
			if ($sviluppo_tab and $peco<100 and $su){
				$s.='<div class="row alert alert-info">';
					$s.='<div class="col-sm-8">FUNZIONA: lista e modifica stato, aggiornamento per nuove attivit&agrave;
					<br />MANCA: </div>';
					$s.='<div class="col-sm-4">';
						$s.='<div class="progress-bar bg-'.($peco<80?($peco<33?'danger':'warning'):'success').'" style="width:'.$peco.'%;">'.$peco.'%</div>';
					$s.='</div>';
				$s.='</div>';
			}
			$s.='<div class="row py-1">';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-primary btn-block" act="li_att" dom="attivita_bottom" tipo_attivita="a">Lista completa attivit&agrave;</button>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-primary btn-block" act="li_att" dom="attivita_bottom" tipo_attivita="u">Lista attivit&agrave; usate</button>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-warning btn-block" act="agg_att" dom="attivita_bottom">Aggiorna attivit&agrave;</button>';
				$s.='</div>';
				$s.='<div class="col-sm-6">';
				$s.='</div>';
			$s.='</div>'; // agg_att
			$s.='<div id="attivita_bottom"></div>';
			break;
}
		case 'ict':
if (true){		
			$peco=100;
			if ($sviluppo_tab and $peco<100 and $su){
				$s.='<div class="row alert alert-info">';
					$s.='<div class="col-sm-8">FUNZIONA: allievi da trasferire, utenti da eliminare
					<br>TO DO: </div>';
					$s.='<div class="col-sm-4">';
						$s.='<div class="progress-bar bg-'.($peco<80?($peco<33?'danger':'warning'):'success').'" style="width:'.$peco.'%;">'.$peco.'%</div>';
					$s.='</div>';
				$s.='</div>';
			}
			$s.='<div class="row py-1">';
				$s.='<div class="col-sm-4">';
					$s.='<select name="ict_azione" id="ict_azione" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep>';
						$s.='<option data-tokens="adt" value="adt">Allievi da trasferire</option>';
						$s.='<option data-tokens="ude" value="ude">Utenti da eliminare</option>';
						$s.='<option data-tokens="at" value="at">Aggiornamento tabelle</option>';
						$s.='<option data-tokens="llw" value="llw">Log last Week</option>';
						$s.='<option data-tokens="lle" value="lle">Log last Execution</option>';
						if ($su){
							$s.='<option data-tokens="acmgf" value="acmgf">Aggiorna CMG_BKP Full <strong><i class="text-warning fas fa-clock"></i></strong></option>';
							$s.='<option data-tokens="acmg" value="acmg">Aggiorna CMG_BKP</option>';
						}
						$s.='<option data-tokens="al" value="al" data-content="Anomalie LDAP <strong><i class=\'text-warning fas fa-clock\'></i></strong>">Anomalie LDAP</option>';
						$s.='<option data-tokens="anaal" value="anaal">Anagagrafiche non agganciate a LDAP</option>';
						$s.='<option data-tokens="rng" value="rng">Controllo Ruoli gestiti in AB_RUOLO_NEW</option>';
						$s.='<option data-tokens="san" value="san">Stessa data e luogo di nascita in AB_CAN</option>';
						$s.='<option data-tokens="ancald" value="ancald">Anomalie caratteri in LDAP</option>';
					$s.='</select>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<input type="text" placeholder="scadenza dalla data" name="ict_dada" id="ict_dada" class="form-control form-control-sm" dto />';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<input type="text" placeholder="scadenza alla data" name="ict_ada" id="ict_ada" class="form-control form-control-sm" dto />';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-warning btn-block" act="ict_cerca" dom="ict_bottom" getf="ict_azione,ict_dada,ict_ada">Cerca / Esegui</button>';
				$s.='</div>';
			$s.='</div>';
			$s.='<div id="ict_bottom"></div>';
			break;	
}
		case 'acl':
if (true){		
			$peco=0;
			if ($sviluppo_tab and $peco<100 and $su){
				$s.='<div class="row alert alert-info">';
					$s.='<div class="col-sm-8">FUNZIONA: legge 
					<br />MANCA: </div>';
					$s.='<div class="col-sm-4">';
						$s.='<div class="progress-bar bg-'.($peco<80?($peco<33?'danger':'warning'):'success').'" style="width:'.$peco.'%;">'.$peco.'%</div>';
					$s.='</div>';
				$s.='</div>';
			}
			$s.='<div class="row py-1">';
				$s.='<div class="col-sm-2">';
					$s.='<button act="e_acl" class="btn btn-primary btn-sm btn-block" fn="im_acl.json" href="#" title="im_acl.json">edit im_acl.json</button>';		
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<button dom="mm" act="s_acl" class="btn btn-success btn-sm btn-block" href="#" gett="eFileName">salva</button>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='Font: (<strong><span id="eFontSize"></span></strong>) ';
					$s.=' <button title="aumenta" act="+" class="btn btn-warning btn-sm" href="#">+</button>';
					$s.=' <button title="diminuisci" act="-" class="btn btn-warning btn-sm" href="#">-</button>';
				$s.='</div>';
				$s.='<div class="col-sm-4">';
					$s.='<h2><span id="eFileName">im_acl.json</span></h2>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
				$s.='</div>';
			$s.='</div>';
			$s.='<div class="row">';
				$s.='<div class="col-sm-12">';
					$s.='<div id="editor" style="position: absolute; top: 0; right: 0; bottom: 0; left: 0; width: 100%; height: 500px;"></div>';
				$s.='</div>';
			$s.='</div>';			
			$s.='<div id="acl_bottom"></div>';
			break;
}
	}
	if ($s!=''){$s='<br />'.$s;}
	return $s;
}
function get_table_data($a,$g,$d,$n,$t='o',$dtb=true){
	global $conn_new, $ad_conn;
	$s='';

	// mi autentico come "User Read"
	$ldapBind = ldap_bind($ad_conn,'sssapisa.it/OpenLdap/Servizi ICT/User Read','readuser'); // ok
	$e=get_ldap_error();if ($e!=''){$s.=$e;} // non mi sono autenticato

	// 	a		=	array risultato di una query di lettura dati
	// 	g		=	array gruppi campi
	//	d		=	array intestazioni
	//	n		= nome (id) della tabella
	if (empty($_SESSION['IAM']['uid_login'])){return;}
	if (!is_array($a) or empty($a)){return '';}
	if (empty($g) or empty($d)){
		if ($t=='o') {$gt = array_keys($a);}
		if ($t=='m') {$gt = array_keys($a[0]);}
		if ($t=='csv') {$gt=$a[0];}
		// $gt = array_keys($a);
		$g=array();
		for($i=0; $i<count($gt); $i++){
			array_push($g,array($gt[$i]));
		}
		if ($t=='o') {$d = array_keys($a);}
		if ($t=='m') {$d = array_keys($a[0]);}
		if ($t=='csv') {$d=$a[0];}
		// $d = array_keys($a);
		for($i=0; $i<count($d); $i++){
			$d[$i] = str_replace("_", " ", $d[$i]);
			$d[$i] = strtolower($d[$i]);
		}
	}
	if ($t=='o') {$r=count($a[$g[0][0]]);}
	if ($t=='m') {$r=count($a);}
	if ($t=='csv') {
		unset($a[0]); // remove item at index 0
		$a = array_values($a); // 'reindex' array 
		$r=count($a); 
	}
	// file_put_contents('_tmp.log', "a: ".print_r($a,true)."\r\n", FILE_APPEND | LOCK_EX);
		
	// $r=count($a[$g[0][0]]);	// numero righe
	if ($r > 0) {
		$s.='<div class="row bg-light">';
			$s.='<div class="col-sm-2">';
				if ($dtb){
					$s.='<button class="btn btn-info btn-sm btn-block" act="toggleDTX" tabledom="tb-'.$n.'">Toggle DataTables</button>';
				}
			$s.='</div>';
			$s.='<div class="col-sm-2">';
//				crea_csv($a,$g,$d,$n);
//				$nf=str_replace("-","_",'tables/'.$_SESSION['IAM']['uid_login'].'_'.$n.'.csv');
//				$s.='<a class="btn btn-success btn-sm btn-block" href="'.$nf.'" target="_blank">CSV</a>';
			$s.='</div>';
			$s.='<div class="col-sm-2">';
			$s.='</div>';
			$s.='<div id="alert-'.$n.'" class="col-sm-6"></div>';
		$s.='</div>';

		$s.='<table class="table table-striped table-sm table-responsive" id="tb-'.$n.'"';
//		if ($r > 10) {$s.=' hasmanyrows="1"';}
		$s.='><thead><tr>';
		if ($n=='anomalie_ldap'){$s.='<th></th>';}
		if ($n=='lista_eccezioni'){$s.='<th></th>';}
		// if ($n=='subscribers_ml'){$s.='<th>AD</th><th></th>';}
		if ($n=='subscribers_ml'){$s.='<th>AD</th>';}
		if ($n=='lista_attivita'){$s.='<th></th>';}
		if (in_array($n,["ce_aree","ce_moduli","ce_funzioni","ce_gruppi","ce_profili","ce_contesti","ce_ruoli","vi_gruppi","vi_profili","vi_contesti","vi_ruoli","co_gruppi","co_profili","co_contesti","co_ruoli"])){$s.='<th></th>';}
		for($di=0; $di < count($d); $di++){
			$s.='<th>'.$d[$di].'</th>';
		}
		if ($n=='subscribers_ml' and $di==count($d)){
			$s.='<th>Nome</th><th>Cognome</th><th>Ruolo</th><th>Afferenza</th><th>Inquadramento</th><th>Fine rapporto</th>';
		}
		$s.='</tr></thead><tbody>';
		for($i=0; $i < $r; $i++){						// loop sulle righe
			$s.='<tr>';
			for($di=0; $di<count($d); $di++){	// loop sulle descrizioni delle colonne (gruppi)
				if ($n=='anomalie_ldap'){
					if ($di==0){
						$s.='<td><button title="dettaglio" class="btn btn-info btn-sm btn-block" act="anomalie_dett" auid="'.$a['A_UID'][$i].'" luid="'.$a['L_UID'][$i].'" acf="'.$a['COD_FISC'][$i].'" lcf="'.$a['CF'][$i].'" dom="mm">D</button></td>';
					}
				}
				if ($n=='lista_eccezioni' and $di==0){
					$s.='<td>';
						if ($a['EP'][$i]==1) {
							$s.='<button class="btn btn-warning btn-sm btn-block" act="ne" cf="'.$a['COD_FISC'][$i].'"  kr="'.$a['KR'][$i].'" dom="eczn_bottom" tb="e">Modifica</button>';
							$s.='<button class="btn btn-danger btn-sm btn-block" act="de" cf="'.$a['COD_FISC'][$i].'" nc="'.$a['NOME'][$i].' '.$a['COGNOME'][$i].'" dom="mm">Cancella</button>';
						} else {
							$s.='<button class="btn btn-primary btn-sm btn-block" act="ne" cf="'.$a['COD_FISC'][$i].'" kr="'.$a['KR'][$i].'" dom="eczn_bottom" tb="c">Inserisci</button>';
						}
					$s.='</td>';
				}
				if ($n=='subscribers_ml' and $di==0){
					$auid=explode('@',$a['UID'][$i]);
					$ta='ab_can'; $css='';
					$indice=array_search($a['UID'][$i],$_SESSION['IAM'][$ta]['MAIL']);
					if ($indice === false) { // provo a cercarlo tramite la forma n.cognome
						$indice=array_search($auid[0],$_SESSION['IAM'][$ta]['LDAP_UID']);
						if ($indice === false) { // provo a cercarlo in ab_csn
							$ta='ab_csn'; $css='alert-danger';
							$indice=array_search($a['UID'][$i],$_SESSION['IAM'][$ta]['MAIL']);
							if ($indice === false) { // provo a cercarlo tramite la forma n.cognome
								$indice=array_search($auid[0],$_SESSION['IAM'][$ta]['LDAP_UID']);
							}
						}
					}

					// leggo se presente nella ML in AD per confronto
					if ($indice !== false){

//  						$nc=strtolower($_SESSION['IAM'][$ta]['NOME'][$indice].'.'.$_SESSION['IAM'][$ta]['COGNOME'][$indice]).'@sssapisa.it';
//  						$nl=$_REQUEST['nml'];
//  						$pl='OU=Liste Prova, OU=ADprova, DC=sssapisa, DC=it';
//  						$sr = ldap_search($ad_conn, $pl, "(displayname=$nl)",array('Members'));
//  						$en = ldap_get_entries($ad_conn, $sr);
//  						$nen = ldap_count_entries($ad_conn, $sr);	// numero elementi trovati; dovrebbe essere solo 1
//  						$s1='<br />'.safe_json_encode($en[0]);
//  						// $s2 = $ad_conn->group()->members('ML_'.$_REQUEST['nml']); // NO
//  						// $s1=safe_json_encode($s2);
						
// $dn = 'OU=Liste Prova, OU=ADprova, DC=sssapisa, DC=it'; // Location of groups in directory
// $attributes = ['members'];
// $attributes = ['showinaddressbook'];
// $filter = "(displayname=$nl)";

// $search = ldap_search($ad_conn, $dn, $filter, $attributes);
// $results = ldap_get_entries($ad_conn, $search);

// $r=$results[0]['showinaddressbook'][0];
// $sr = ldap_list($ad_conn, $r, "uid=*");
// $en = ldap_get_entries($ad_conn, $sr);
// $s1='<br />'.safe_json_encode($en);

// $s1=safe_json_encode($results);	
						
/*
{
	"objectclass": {
		"count": 2,
		"0": "top",
		"1": "group"
	},
	"0": "objectclass",
	"cn": {
		"count": 1,
		"0": "ML_PhDstudents"
	},
	"1": "cn",
	"distinguishedname": {
		"count": 1,
		"0": "CN=ML_PhDstudents,OU=Liste Prova,OU=ADprova,DC=sssapisa,DC=it"
	},
	"2": "distinguishedname",
	"instancetype": {
		"count": 1,
		"0": "4"
	},
	"3": "instancetype",
	"whencreated": {
		"count": 1,
		"0": "20220912133633.0Z"
	},
	"4": "whencreated",
	"whenchanged": {
		"count": 1,
		"0": "20220912133643.0Z"
	},
	"5": "whenchanged",
	"displayname": {
		"count": 1,
		"0": "PhDstudents"
	},
	"6": "displayname",
	"usncreated": {
		"count": 1,
		"0": "401369840"
	},
	"7": "usncreated",
	"info": {
		"count": 1,
		"0": "Allievi perfezionandi e dottorandi"
	},
	"8": "info",
	"usnchanged": {
		"count": 1,
		"0": "401369959"
	},
	"9": "usnchanged",
	"proxyaddresses": {
		"count": 2,
		"0": "smtp:ML_PhDstudents@alumnisssup.mail.onmicrosoft.com",
		"1": "SMTP:ML_PhDstudents@santannapisa.it"
	},
	"10": "proxyaddresses",
	"garbagecollperiod": {
		"count": 1,
		"0": "1209600"
	},
	"11": "garbagecollperiod",
	"name": {
		"count": 1,
		"0": "ML_PhDstudents"
	},
	"12": "name",
	"objectguid": {
		"count": 1,
		"0": "%#D;KÓMWê"
	},
	"13": "objectguid",
	"objectsid": {
		"count": 1,
		"0": "\u0001\u0005\u0000\u0000\u0000\u0000\u0000\u0005\u0015\u0000\u0000\u0000\u000EçæÔñIçõuÍö\u0000n\u0000\u0000"
	},
	"14": "objectsid",
	"samaccountname": {
		"count": 1,
		"0": "ML_PhDstudents-11903508968"
	},
	"15": "samaccountname",
	"samaccounttype": {
		"count": 1,
		"0": "268435457"
	},
	"16": "samaccounttype",
	"showinaddressbook": {
		"count": 2,
		"0": "CN=All Groups(VLV),CN=All System Address Lists,CN=Address Lists Container,CN=First Organization,CN=Microsoft Exchange,CN=Services,CN=Configuration,DC=sssapisa,DC=it",
		"1": "CN=All Recipients(VLV),CN=All System Address Lists,CN=Address Lists Container,CN=First Organization,CN=Microsoft Exchange,CN=Services,CN=Configuration,DC=sssapisa,DC=it"
	},
	"17": "showinaddressbook",
	"managedby": {
		"count": 1,
		"0": "CN=Administrator,CN=Users,DC=sssapisa,DC=it"
	},
	"18": "managedby",
	"legacyexchangedn": {
		"count": 1,
		"0": "/o=First Organization/ou=Exchange Administrative Group (FYDIBOHF23SPDLT)/cn=Recipients/cn=b3f776f8aa6d40d88eb5d12dda2f29b3-ML_Ph"
	},
	"19": "legacyexchangedn",
	"grouptype": {
		"count": 1,
		"0": "8"
	},
	"20": "grouptype",
	"objectcategory": {
		"count": 1,
		"0": "CN=Group,CN=Schema,CN=Configuration,DC=sssapisa,DC=it"
	},
	"21": "objectcategory",
	"dscorepropagationdata": {
		"count": 1,
		"0": "16010101000000.0Z"
	},
	"22": "dscorepropagationdata",
	"mail": {
		"count": 1,
		"0": "ML_PhDstudents@santannapisa.it"
	},
	"23": "mail",
	"msexchgroupmembercount": {
		"count": 1,
		"0": "0"
	},
	"24": "msexchgroupmembercount",
	"msexchgroupdepartrestriction": {
		"count": 1,
		"0": "1"
	},
	"25": "msexchgroupdepartrestriction",
	"mailnickname": {
		"count": 1,
		"0": "ML_PhDstudents"
	},
	"26": "mailnickname",
	"msexchtransportrecipientsettingsflags": {
		"count": 1,
		"0": "0"
	},
	"27": "msexchtransportrecipientsettingsflags",
	"msexchumdtmfmap": {
		"count": 3,
		"0": "emailAddress:6574378833687",
		"1": "lastNameFirstName:74378833687",
		"2": "firstNameLastName:74378833687"
	},
	"28": "msexchumdtmfmap",
	"msexchmailboxfolderset": {
		"count": 1,
		"0": "0"
	},
	"29": "msexchmailboxfolderset",
	"msexchgroupsecurityflags": {
		"count": 1,
		"0": "0"
	},
	"30": "msexchgroupsecurityflags",
	"msexchgroupexternalmembercount": {
		"count": 1,
		"0": "0"
	},
	"31": "msexchgroupexternalmembercount",
	"msexchaddressbookflags": {
		"count": 1,
		"0": "1"
	},
	"32": "msexchaddressbookflags",
	"msexchgroupjoinrestriction": {
		"count": 1,
		"0": "0"
	},
	"33": "msexchgroupjoinrestriction",
	"msexchmdbrulesquota": {
		"count": 1,
		"0": "256"
	},
	"34": "msexchmdbrulesquota",
	"msexchlocalizationflags": {
		"count": 1,
		"0": "0"
	},
	"35": "msexchlocalizationflags",
	"reporttooriginator": {
		"count": 1,
		"0": "TRUE"
	},
	"36": "reporttooriginator",
	"msexchmoderationflags": {
		"count": 1,
		"0": "6"
	},
	"37": "msexchmoderationflags",
	"msexchrecipientdisplaytype": {
		"count": 1,
		"0": "1"
	},
	"38": "msexchrecipientdisplaytype",
	"msexchmailboxauditenable": {
		"count": 1,
		"0": "FALSE"
	},
	"39": "msexchmailboxauditenable",
	"msexcharbitrationmailbox": {
		"count": 1,
		"0": "CN=SystemMailbox{1f05a927-35cc-47b8-9ecc-c2e24d0e0b3e},CN=Users,DC=sssapisa,DC=it"
	},
	"40": "msexcharbitrationmailbox",
	"msexchrecipientsoftdeletedstatus": {
		"count": 1,
		"0": "0"
	},
	"41": "msexchrecipientsoftdeletedstatus",
	"internetencoding": {
		"count": 1,
		"0": "0"
	},
	"42": "internetencoding",
	"msexchhidefromaddresslists": {
		"count": 1,
		"0": "TRUE"
	},
	"43": "msexchhidefromaddresslists",
	"msexchversion": {
		"count": 1,
		"0": "44220983382016"
	},
	"44": "msexchversion",
	"msexchbypassaudit": {
		"count": 1,
		"0": "FALSE"
	},
	"45": "msexchbypassaudit",
	"msexchuseraccountcontrol": {
		"count": 1,
		"0": "0"
	},
	"46": "msexchuseraccountcontrol",
	"msexchprovisioningflags": {
		"count": 1,
		"0": "0"
	},
	"47": "msexchprovisioningflags",
	"msexchpoliciesincluded": {
		"count": 2,
		"0": "39be592c-8798-460f-ab45-20d115293a30",
		"1": "{26491cfc-9e50-4857-861b-0cb8df22b5d7}"
	},
	"48": "msexchpoliciesincluded",
	"msexchrolegrouptype": {
		"count": 1,
		"0": "0"
	},
	"49": "msexchrolegrouptype",
	"msexchrequireauthtosendto": {
		"count": 1,
		"0": "TRUE"
	},
	"50": "msexchrequireauthtosendto",
	"msexchmailboxauditlogagelimit": {
		"count": 1,
		"0": "7776000"
	},
	"51": "msexchmailboxauditlogagelimit",
	"count": 52,
	"dn": "CN=ML_PhDstudents,OU=Liste Prova,OU=ADprova,DC=sssapisa,DC=it"
}
*/ 					
						$s.='<td>'.get_alert('<strong>OK</strong>','success',true).'</td>';
						// $s1='';
						// $s.='<td>'.get_alert('<strong>OK</strong>'.$s1,'success').'</td>';
					} else {
						$s.='<td>'.get_alert('<strong>NO</strong>','danger',true).'</td>';
					}
/*					
					if (strtolower($a['MODE'][$i])=='unsubscribe'){
						$s.='<td>Just Uns.</td>';
					} else {
						$s.='<td><button title="Unsubscribe '.$a['UID'][$i].'" class="btn btn-danger btn-sm btn-block" act="unsub_ml" uid="'.$a['UID'][$i].'" nml="'.$_REQUEST['nml'].'" dom="mm">Unsubscribe</button></td>';
					}
*/
				}
				if ($n=='lista_attivita' and $di==0){
					$s.='<td><button title="toggle" class="btn btn-secondary btn-sm btn-block" act="tg_att" attivita="'.$a['ATTIVITA'][$i].'" dom="mm" gett="ATT_'.$a['ATTIVITA'][$i].'_F_ATTIVO">toggle</button></td>';
				}
				if (in_array($n,["ce_aree","ce_moduli","ce_funzioni","ce_gruppi","ce_profili","ce_contesti","ce_ruoli","vi_gruppi","vi_profili","vi_contesti","vi_ruoli","co_gruppi","co_profili","co_contesti","co_ruoli"]) and $di==0){
					$tt=explode('_',$n);
					$s.='<td>';
					if (!empty($a['KG'][$i])){
						$s.='<button title="visualizza utenti del gruppo" class="btn btn-secondary btn-sm" act="visute_pu" tt0="'.$tt[0].'" tt1="'.$tt[1].'" dom="mm" k="'.$a['KG'][$i].'">UG</button>';
						$s.='<button title="visualizza ruoli del gruppo" class="btn btn-secondary btn-sm" act="visruo_pu" tt0="'.$tt[0].'" tt1="'.$tt[1].'" dom="mm" k="'.$a['KG'][$i].'">RG</button>';
					}
					if (!empty($a['KP'][$i])){
						$s.='<button title="visualizza utenti del profilo" class="btn btn-secondary btn-sm" act="visute_pu" tt0="'.$tt[0].'" tt1="'.$tt[1].'" dom="mm" k="'.$a['KP'][$i].'">UP</button>';
						$s.='<button title="visualizza ruoli del profilo" class="btn btn-secondary btn-sm" act="visruo_pu" tt0="'.$tt[0].'" tt1="'.$tt[1].'" dom="mm" k="'.$a['KP'][$i].'">RP</button>';
					}
					if (!empty($a['KC'][$i])){
						$s.='<button title="visualizza utenti del contesto" class="btn btn-secondary btn-sm" act="visute_pu" tt0="'.$tt[0].'" tt1="'.$tt[1].'" dom="mm" k="'.$a['KC'][$i].'">UC</button>';
					}
					if (!empty($a['KR'][$i])){
						$s.='<button title="visualizza utenti del ruolo" class="btn btn-secondary btn-sm" act="visute_pu" tt0="'.$tt[0].'" tt1="'.$tt[1].'" dom="mm" k="'.$a['KR'][$i].'">UR</button>';
					}
					$s.='</td>';					
				}
				$s.='<td>';
				for($gi=0; $gi<count($g[$di]); $gi++){	// loop sui campi del gruppo
					$s.=(($gi==0)?'':'<br />');
					$nc=$g[$di][$gi];	// nome campo
					$title='';
					if ($t=='o') {
						$tmp=htmlentities($a[$nc][$i]);
						if ($n=='anomalie_ldap'){
							$title="";
							if ($nc=='COD_FISC'){
								$line1=htmlentities($a[$nc][$i]);
								$line2=htmlentities($a['CF'][$i]);
								$title.=diffline($line1, $line2);
							}
							if ($nc=='CF'){
								$line1=htmlentities($a[$nc][$i]);
								$line2=htmlentities($a['COD_FISC'][$i]);
								$title.=diffline($line1, $line2);
							}
							$title.='" data-toggle="tooltip'; 
							// $title=str_replace('"','\"',$title);
						}
						if ($n=='lista_eccezioni'){
							if ($a['ATTIVO'][$i]==1){$tmp=get_alert($tmp,'success',true);} else {$tmp=get_alert($tmp,'danger',true);}
						}
					}
					if ($t=='m') {
						$tmp=htmlentities($a[$i][$nc]);
					}
					if ($t=='csv') {
						// if(empty($a[$i][$di])){$tmp='ERR i: '.$i.' - di: '.$di;} else {$tmp=htmlentities($a[$i][$di]);}
						if(empty($a[$i][$di])){$tmp='';} else {$tmp=$a[$i][$di];}
					}
					if ($n=='lista_attivita' and $nc=='F_ATTIVO'){$s.='<span id="ATT_'.$a['ATTIVITA'][$i].'_F_ATTIVO">';}
					$s.='<span title="'.$title.'">'.$tmp.'</span>';
					if ($n=='lista_attivita' and $nc=='F_ATTIVO'){$s.='</span>';}
				}
				$s.='</td>';
				if ($n=='subscribers_ml' and $di==count($g[$di])){
					if ($indice !== false){
						$s.='<td class="'.$css.'">'.$_SESSION['IAM'][$ta]['NOME'][$indice].'</td>';
						$s.='<td class="'.$css.'">'.$_SESSION['IAM'][$ta]['COGNOME'][$indice].'</td>';
						$s.='<td title="'.$_SESSION['IAM'][$ta]['KR'][$indice].'" class="'.$css.'">'.$_SESSION['IAM'][$ta]['DR'][$indice].'</td>';
						$s.='<td title="'.$_SESSION['IAM'][$ta]['KA'][$indice].'" class="'.$css.'">'.$_SESSION['IAM'][$ta]['DA'][$indice].'</td>';
						$s.='<td title="'.$_SESSION['IAM'][$ta]['KI'][$indice].'" class="'.$css.'">'.$_SESSION['IAM'][$ta]['DI'][$indice].'</td>';
						$s.='<td class="'.$css.'">'.$_SESSION['IAM'][$ta]['DT_RAP_FIN'][$indice].'</td>';
					} else {
						$s.='<td></td><td></td><td></td><td></td><td></td><td></td>';
					}
				}
			}
			$s.='</tr>';
		}
		$s.='</tbody></table>';
	}
	return $s;
}
function get_dizionari(){
	global $conn_new;
	if (empty($_SESSION['IAM']['lista_ruoli'])){
		// $sql="select kr k, dr || ' (' || kr || ')' d, dr, gest, 1 usato from (select distinct kr, dr from c##sss_import.ab_can) union select k_ruolo k, ruolo || ' (' || k_ruolo || ')' d, ruolo dr, null gest, 0 usato from c##sss_import.ab_ruolo_new where k_ruolo not in (select distinct kr from c##sss_import.ab_can) order by dr";
		$sql="select k_ruolo k, ruolo || ' (' || k_ruolo || ')' d, k_ruolo kr, ruolo dr, ANNI_WARNING from c##sss_import.ab_ruolo_new order by k";
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$_SESSION['IAM']['lista_ruoli']=$a[2];
		}
	}
	if (empty($_SESSION['IAM']['lista_afferenze'])){
		// $sql="select ka k, da || ' (' || ka || ')' d, da, gest, 1 usato from (select distinct ka, da from c##sss_import.ab_can) union select uo k, descr || ' (' || uo || ')' d, descr da, null gest, 0 usato from c##sss_import.ab_didorgsedi where typ='org' and uo not in (select distinct ka from c##sss_import.ab_can) order by da";	
		// $sql="select uo k, descr || ' (' || uo || ')' d, uo ka, descr da from c##sss_import.ab_didorgsedi where typ='org' order by k";
		$sql="select uo k, descr || ' (' || uo || ')' d, uo ka, breve da from c##sss_import.ab_didorgsedi where typ='org' order by k";
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$_SESSION['IAM']['lista_afferenze']=$a[2];
		}
	}

	if (empty($_SESSION['IAM']['tbmailist'])){
		$sql="select * from c##sss_import.tbmailist";	
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$_SESSION['IAM']['tbmailist']=$a[2];
		}
	}

	if (empty($_SESSION['IAM']['ab_can'])){
		// $sql="select a.*, to_char(a.DT_RAP_FIN, 'dd/mm/yyyy') as DTF from c##sss_import.ab_can a";

		$sql = "select a.*, o.*
		from (select dos.breve, can.*, to_char(can.DT_RAP_FIN, 'dd/mm/yyyy') as DTF from C##SSS_IMPORT.AB_CAN can, C##SSS_IMPORT.AB_DIDORGSEDI dos where can.ka=dos.uo and dos.typ = 'org') a";
		// $sql.=", ( select matricola, cd_csa, cd_posizorg, cd_tipo_posizorg, ds_tipo_posizorg, nome_breve_uo, denominazione_po, dt_inizio_cop, dt_fine_cop, perc_utilizzo_cop, fl_afferenza, fl_responsabile from C##SSS_TABLES.V_IE_RU_ORGANICO_COP where trunc(dt_inizio_cop) <= trunc(sysdate) and trunc(dt_fine_cop) >= trunc(sysdate) ) o";
		$sql.=", ( select distinct matricola, fl_responsabile from C##SSS_TABLES.V_IE_RU_ORGANICO_COP where trunc(dt_inizio_cop) <= trunc(sysdate) and trunc(dt_fine_cop) >= trunc(sysdate) ) o";
		$sql.=" where a.k = o.matricola(+)";
		$sql.=" order by a.COGNOME, a.NOME";

		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$_SESSION['IAM']['ab_can']=$a[2];
		}
	}

	if (empty($_SESSION['IAM']['ab_csn'])){
		$sql="select * from c##sss_import.ab_csn";	
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$_SESSION['IAM']['ab_csn']=$a[2];
		}
	}
	if (empty($_SESSION['IAM']['lista_comuni'])){	// comuni
		$sql="select cd_catasto k, ds_comune || ' (' || cd_sigla || ') (' || cd_catasto || ')' as d from C##SSS_TABLES.V_IE_AC_COMUNI order by ds_comune";
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$_SESSION['IAM']['lista_comuni']=$a[2];
		}
	}
	if (empty($_SESSION['IAM']['utenti_ugov'])){
		$sql="select * from c##sss_import.AB_UTENTI_UGOV_ML";	
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$_SESSION['IAM']['utenti_ugov']=$a[2];
		}
	}
	if (empty($_SESSION['IAM']['cmg_bkp'])){
		$sql="select * from c##sss_import.cmg_bkp";	
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$_SESSION['IAM']['cmg_bkp']=$a[2];
		}
	}
}
// ----------------------------------------------------------------------------
// FUNZIONI SPECIFICHE DEL PROGRAMMA
// ----------------------------------------------------------------------------
// --- account ---
/*
-- storico_ldap.richieste_account definition
DROP TABLE storico_ldap.richieste_account;
CREATE TABLE `richieste_account` (
  `ra_k` int NOT NULL AUTO_INCREMENT COMMENT 'pk',
  `ra_tipo` varchar(40) NOT NULL COMMENT 'PF=persona fisica, GE=generico, PFG=persona fisica Guest',
  `ra_nome` varchar(100) DEFAULT NULL COMMENT 'solo se ra_tipo in (PF, PFG)',
  `ra_cognome` varchar(100) NOT NULL COMMENT 'cognome o denominazione account generico',
  `ra_mail` varchar(100) DEFAULT NULL,
  `ra_cf` varchar(16) DEFAULT NULL COMMENT 'solo se ra_tipo in (PF, PFG)',
  `ra_mail_notifica` varchar(150) DEFAULT NULL COMMENT 'indirizzo a cui inviare i dati completati',
  `ra_notificata` int NOT NULL DEFAULT '0' COMMENT 'notifica inviata (se indicato l''indirizzo)',
  `ra_dt_notifica` date DEFAULT NULL COMMENT 'data dell''ultima notifica',
  `ra_ruolo` varchar(10) DEFAULT NULL COMMENT 'codice ruolo',
  `ra_afferenza` varchar(10) DEFAULT NULL COMMENT 'codice afferenza',
  `ra_inquadramento` varchar(20) DEFAULT NULL COMMENT 'codice inquadramento',
  `ra_inizio` date DEFAULT (curdate()) COMMENT 'inizio validità',
  `ra_nuovo_inizio` date DEFAULT NULL COMMENT 'in caso di ripristino dell''account è la nuova data di inizio',
  `ra_fine` date DEFAULT NULL,
  `ra_cf_referente` varchar(16) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL COMMENT 'codice fiscale del referente',
  `ra_nc_referente` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL COMMENT 'nome e cognome del referente',
  `ra_stato` varchar(30) NOT NULL DEFAULT 'bozza' COMMENT 'bozza, richiesta, approvata, completata, rifiutata',
  `ra_motivo` varchar(1000) DEFAULT NULL COMMENT 'nota (motivo eventuale rifiuto)',
  `ra_uid` varchar(20) DEFAULT NULL COMMENT 'uid assegnato (max 30 crt)',
  `ra_aliases` varchar(1000) DEFAULT NULL COMMENT 'lista alias separati da |',
  `ra_redirect` varchar(100) DEFAULT NULL COMMENT 'indirizzo email richiesto dopo fine validità per redirect',
  `ra_prima_password` varchar(100) DEFAULT NULL,
  `ra_modifica_password` varchar(100) DEFAULT NULL,
  `ra_cancellabile` int NOT NULL DEFAULT '0' COMMENT 'una volta storicizzato non deve essere più cancellabile ma cambiando di stato può ricominciare il ciclo',
  `ra_note` varchar(1000) DEFAULT NULL,
  `ra_nfcm` varchar(1000) DEFAULT NULL COMMENT 'nota: nome file caricamento massivo con data e ora',
  `ra_ldap_dn_ini` varchar(300) DEFAULT NULL COMMENT 'dn iniziale assegnata su LDAP',
  `ra_ad_motivo` varchar(1000) DEFAULT NULL COMMENT 'nota (motivo eventuale rifiuto) su AD',
  `ra_ad_princ_mail` varchar(100) DEFAULT NULL COMMENT 'mail principale su AD',
  `ra_ad_dn_ini` varchar(300) DEFAULT NULL COMMENT 'dn iniziale assegnata su AD',
  `ra_usr_ins` varchar(100) NOT NULL COMMENT 'utente di primo inserimento',
  `ra_dt_ins` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'data primo inserimento',
  `ra_ip_ins` varchar(15) DEFAULT NULL COMMENT 'utente di primo inserimento',
  `ra_usr_mod` varchar(100) DEFAULT NULL COMMENT 'utente ultima modifica (vedi tabellara_richieste_account_log)',
  `ra_dt_mod` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'data ultima modifica',
  `ra_ip_mod` varchar(15) DEFAULT NULL COMMENT 'utente di primo inserimento',
  UNIQUE KEY `ra_k_pk` (`ra_k`) USING BTREE,
  KEY `richieste_account_ra_cf_IDX` (`ra_cf`) USING BTREE,
  KEY `richieste_account_ra_uid_IDX` (`ra_uid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=255875 DEFAULT CHARSET=latin1;

-- storico_ldap.richieste_account_alias definition
DROP TABLE storico_ldap.richieste_account_alias;
CREATE TABLE `richieste_account_alias` (
  `raa_k` int NOT NULL AUTO_INCREMENT COMMENT 'pk',
  `raa_ra_k` int NOT NULL COMMENT 'riferimento a richiesta',
  `raa_ra_uid` varchar(100) NOT NULL COMMENT 'uid principale',
  `raa_uid` varchar(100) NOT NULL COMMENT 'alias - di 100 per retrocompatibilità',
  `raa_usr_ins` varchar(100) NOT NULL,
  `raa_dt_ins` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `raa_k_pk` (`raa_k`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=404711 DEFAULT CHARSET=latin1;

-- storico_ldap.richieste_account_log definition
DROP TABLE storico_ldap.richieste_account_log;
CREATE TABLE `richieste_account_log` (
  `ral_k` int NOT NULL AUTO_INCREMENT COMMENT 'pk',
  `ral_ra_k` int NOT NULL COMMENT 'riferimento a richiesta',
  `ral_d` varchar(1000) DEFAULT NULL COMMENT 'descrizione del log',
  `ral_stato` varchar(100) DEFAULT NULL,
  `ral_usr_ins` varchar(100) NOT NULL,
  `ral_dt_ins` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ral_ip` varchar(100) DEFAULT NULL,
  UNIQUE KEY `ral_k_pk` (`ral_k`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=266 DEFAULT CHARSET=latin1;

-- storico_ldap.richieste_account_stati definition
DROP TABLE storico_ldap.richieste_account_stati;
CREATE TABLE `richieste_account_stati` (
  `ras_id` bigint NOT NULL AUTO_INCREMENT,
  `ras_stato` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `ras_ord_01` bigint NOT NULL,
  `ras_comment` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`ras_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

-----------------------------------------------------------------------------------------

-- lettura denormalizzata su account Annamaria / Italo
CREATE OR REPLACE ALGORITHM = UNDEFINED VIEW `v_MailAaccount_ex` AS select `ma`.`id` AS `id`, `ma`.`nome` AS `uid`, `ma`.`idAnagrafe` AS `idAnagrafe`, `ma`.`redirezione` AS `redirezione`, `ma`.`password` AS `password`, `ma`.`cryptpasswd` AS `cryptpasswd`, `ma`.`apertura` AS `apertura`, `ma`.`chiusura` AS `chiusura`, `a`.`cognome` AS `cognome`, `a`.`nome` AS `nome`, `g`.`id` AS `idTblGID`, `g`.`nome` AS `gid_k`, `g`.`descrizione` AS `descrizione`, cast(from_unixtime(`ma`.`apertura`) as date) AS `inizio`, cast(from_unixtime(`ma`.`chiusura`) as date) AS `fine`
from((`tblMailAccount` `ma`
left join `tblAnagrafe` `a` on
((`ma`.`idAnagrafe` = `a`.`id`)))
left join `tblGID` `g` on
((`a`.`idGID` = `g`.`id`)))
order by `ma`.`apertura`;


*/
function cfla_alumni(){						// carica file lista account alumni
	$s='';
	$w='';
	$m='';
	$e='';
	$tipo='';
	if (!empty($_REQUEST['tipo'])){$tipo=$_REQUEST['tipo'];}
	if (!empty($_REQUEST['tipo'])){
		$t="<h2>Carica file</h2>";
		$s=get_upload_dialog($t,get_alert($m,'success').$w,'carica un file csv:','.csv',$tipo,'csv_default');
	}
	if (isset($_FILES)){			// chiamata per upload di file
		$_SESSION['IAM']['last_file_uploaded']='';
		if (!empty($_FILES)){
			$tit="<h2>Carica file</h2>"; $b=""; $m=''; $m1=''; $path='files/';
			try {
				$a=array_keys($_FILES);
				$aun=$a[0];
				$pnf=$path.$_FILES[$aun]['name'];		// path nome file target
			} catch(Throwable $e) {
				$m.=error_get_last();
			}
			if (!is_array($_FILES[$aun])){
				$m.="File non caricato correttamente";
			} else {
				$maxSize=5000000;
				$aea=array('csv');
				if ($_FILES[$aun]['size'] > $maxSize and $maxSize > 0) {
					$smb=round($maxSize/1000000);
					$m.='<br><div class="alert alert-danger">Le dimensioni del file <strong>'.$_FILES[$aun]['name'].'</strong> eccedono il massimo di '.$smb.'Mb.</div>';
					// unlink($pnf);
				}
				$ext=strtolower(pathinfo($_FILES[$aun]['name'],PATHINFO_EXTENSION));
				if (!in_array($ext, $aea)){
					$m.='<br><div class="alert alert-danger">Puoi caricare solo file di tipo <strong>'.implode(', ',$aea).'</strong></div>';
				}
			}							
			if ($m == '' and $_FILES[$aun]['error'] == UPLOAD_ERR_OK) {
				try {
					move_uploaded_file($_FILES[$aun]['tmp_name'], $pnf);
					$_SESSION['IAM']['last_file_uploaded']=$pnf;
				} catch(Throwable $e) {
					tolog(print_r(error_get_last(),true));
				}
				$m.=get_alert('il file: <strong>'.$_FILES[$aun]['name'].'</strong> di '.$_FILES[$aun]['size'].' byte &egrave; stato correttamente caricato.','success');
				$stl='bg-success text-center text-white';
				if (!empty($_REQUEST['sact'])){if ($_REQUEST['sact']=='alu'){$m1=aau_alu();}}
			} else {
				$m.=get_alert('il file: <strong>'.$_FILES[$aun]['name'].'</strong> di '.$_FILES[$aun]['size'].' byte <strong>NON</strong> &egrave; stato <strong>caricato.</strong>','danger');
				$stl='bg-danger text-center text-white';
			}
			$r=array('tit'=>$tit,'msg'=>$m.$m1,'stl'=>$stl,'btn'=>$b,'dom'=>'mm');
			$r['domm'][0]['domf']='ga_bottom';	$r['domm'][0]['domc']=get_ra_stato(); // default rileggo la tabella
			$s=safe_json_encode($r);
		}
	}	
	return $s;
}
function aau_alu(){
	global $devel, $conn_sl;

// file_put_contents('_tmp.log', 'aau_alu - inizio'."\r\n", FILE_APPEND | LOCK_EX);

	$nome='account';
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}	// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}	// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}	// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}	// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict

	if (empty($_SESSION['IAM']['last_file_uploaded'])){
		return get_alert('File non specificato','danger');
	}
	$nf=$_SESSION['IAM']['last_file_uploaded'];	// nome del file caricato

	$s='';	// output
	// if ($devel and $su){$s.=get_alert(json_encode($_REQUEST));}
	$e='';	// errori
	$w='';	// warnings

	$file = utf8ize(file_get_contents($nf));
	$eol=detectEol($file, chr(10));

	$acsv=explode(chr(13).chr(10),$file); 				// chr(13) = fine riga (\r = CR = chr(13), \n = LF = chr(10))
	$sep=',';
	$campi=explode($sep,strtolower(implode("",explode("\r",implode("",explode("\n",$acsv[0]))))));	// nomi delle colonne (prima riga csv)

	$csv=array();
	for ($i=0; $i<count($acsv); $i++){
		$csvii=implode(" ",explode("\n",$acsv[$i]));	// rimuovo i newline
		if ($i==0){
			array_push($csv,$campi);
		} else {
			$a=explode($sep,$csvii);
			if (count($a) == count($campi)){
				array_push($csv,$a);
			} else {
				$csvii=implode(" ",explode("\r",implode(" ",explode("\n",$acsv[$i-1].$acsv[$i]))));	// rimuovo i nl ed i lf
				$a=explode($sep,$csvii);
				if (count($a) == count($campi)){
					array_push($csv,$a);
				}
			}
		}
	}

	// controlli
	if (count($csv) <= 1){	
		return get_alert('csv vuoto o con sola riga intestazione','danger');
	}

	$sql="delete from storico_ldap.richieste_account_alumni";
	$x=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){
		$e.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');
	}
	$asql=array();
	for ($i=1; $i<count($csv); $i++){
		$ar=$csv[$i];	
		for ($y=0; $y<count($ar); $y++){
			$ar[$y]=implode("''",explode("'",$ar[$y]));
			if (strtolower($ar[$y])=='false'){}
			if (strtolower($ar[$y])=='true'){}
		}
		$sql="insert into storico_ldap.richieste_account_alumni (raal_id,raal_userPrincipalName,raal_displayName,raal_objectType,raal_userType,raal_isUser,raal_isGroup,raal_isGuest) values ('".implode("','",$ar)."')";
		$sql=implode('0',explode("'False'",$sql));
		$sql=implode('1',explode("'True'",$sql));
		try {
			$x=mysqli_query($conn_sl,$sql);
		} catch(Throwable $e) {
			$e.=error_get_last();
		}
		if (!empty(mysqli_error($conn_sl))){
			$e.=get_alert('<strong>'.$sql.'</strong><br />errore alla riga '.$i.' '.mysqli_error($conn_sl),'danger');
			break;
		}
	}			

	// sistemo il campo raal_ldap_uid
	$sql="select * from storico_ldap.richieste_account_alumni";
	$x=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){
		$e.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');
	}
	if (mysqli_num_rows($x) > 0) {	// dati trovati
		foreach($x as $xi){
			if (!empty($xi['raal_userPrincipalName'])){
				$ma=explode('@',$xi['raal_userPrincipalName']);
				$uid=$ma[0];
				$sql="update storico_ldap.richieste_account_alumni set raal_ldap_uid='$uid' where raal_k=".$xi['raal_k'];
				$y=mysqli_query($conn_sl,$sql);
				if (!empty(mysqli_error($conn_sl))){
					$e.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');
				}
			}
		}
	}

	$s=get_alert('Numero righe importate = '.($i - 1),'info');
	// $s=get_alert('Numero righe importate = '.(count($csv)),'info');
	$s.=get_alert($e);
	return $s;
}
function cfla(){									// carica file lista account

	$nome='account';
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}	// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}	// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}	// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}	// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict


	$s='';
	$tipo='';
	if (!empty($_REQUEST['tipo'])){$tipo=$_REQUEST['tipo'];}
	if (!empty($_REQUEST['tipo'])){
		$t="<h2>Carica file</h2>";
		$w=get_alert("Il file deve contenere almeno <strong>Nome, Cognome e codice fiscale</strong> e possibilmente una mail per l'invio delle credenziali<br />Il file pu&ograve; contenere <strong>un massimo di 50 righe</strong> (oltre alla riga di intestazione), le righe eccedenti saranno ignorate",'warning');

		$m=get_alert('<strong>Dati di default se mancanti nel file caricato</strong>','secondary text-center');
		$m.='<form id="f_csv_default">';
			$m.='<div class="row bg-light py-1">';
				$m.='<div class="col-sm-4 text-right">Struttura (Department):</div>';
				$m.='<div class="col-sm-8">';
					$ss='';
					if (!empty($_SESSION['IAM']['lista_afferenze'])){
						for ($y=0; $y < count($_SESSION['IAM']['lista_afferenze']['K']); $y++) {
							$ss.='<option data-tokens="'.$_SESSION['IAM']['lista_afferenze']['K'][$y].'" value="'.$_SESSION['IAM']['lista_afferenze']['K'][$y].'"';
							$ss.='>'.$_SESSION['IAM']['lista_afferenze']['D'][$y].'</option>';
						}
					}
					$m.='<select name="ka" id="ka" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep ><option data-tokens="0" value="0"></option>'.$ss.'</select>';
				$m.='</div>';
			$m.='</div>';
			$m.='<div class="row bg-light py-1">';
				$m.='<div class="col-sm-4 text-right">Categoria (Category):</div>';
				$m.='<div class="col-sm-8">';
					$ss='';
					if (!empty($_SESSION['IAM']['lista_ruoli'])){
						for ($y=0; $y < count($_SESSION['IAM']['lista_ruoli']['K']); $y++) {
							$ss.='<option data-tokens="'.$_SESSION['IAM']['lista_ruoli']['K'][$y].'" value="'.$_SESSION['IAM']['lista_ruoli']['K'][$y].'"';
							$ss.='>'.$_SESSION['IAM']['lista_ruoli']['D'][$y].'</option>';
						}
					}
					$m.='<select name="kr" id="kr" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep ><option data-tokens="0" value="0"></option>'.$ss.'</select>';
				$m.='</div>';
			$m.='</div><br />';

			$m.='<div class="row bg-light py-1">';
				$m.='<div class="col-sm-4 text-right">Data inizio (start date):</div>';
				$m.='<div class="col-sm-8">';
					$m.='<input type="text" name="di" id="di" class="form-control form-control-sm" value="'.date("d/m/Y").'" dto />';
				$m.='</div>';
			$m.='</div>';
			$m.='<div class="row bg-light py-1">';
				$m.='<div class="col-sm-4 text-right">Data fine (end date):</div>';
				$m.='<div class="col-sm-8">';
					$nd=date('Y-m-d', strtotime('+60 days'));
					$m.='<input type="text" name="df" id="df" class="form-control form-control-sm" value="'.date_format(date_create($nd),"d/m/Y").'" dto />';
				$m.='</div>';
			$m.='</div>';
			$m.='<div class="row bg-light py-1">';
				$m.='<div class="col-sm-4 text-right">Referente Interno (Internal Referee):</div>';
				$m.='<div class="col-sm-8">';
					$ss='';
					if (!empty($_SESSION['IAM']['ab_can'])){
						$a=$_SESSION['IAM']['ab_can'];
						$ram1=array('PO','PA','RU','RCRT'); // ruoli ammessi
						$ram2=array('DC','ND'); // ruoli ammessi se responsabili
						for ($y=0; $y < count($a['COD_FISC']); $y++) {
							if (in_array($a['KR'][$y],$ram1) or (in_array($a['KR'][$y],$ram2) and $a['FL_RESPONSABILE'][$y] == 1)){
								$bg='light';
								if ($a['KR'][$y] == 'PO' or $a['KR'][$y] == 'PA'){$bg='success';}
								if ($a['KR'][$y] == 'RU' or $a['KR'][$y] == 'RCRT'){$bg='primary';}
								if ($a['KR'][$y] == 'DC' or $a['KR'][$y] == 'ND' ){$bg='warning';}
								$ss.='<option class="alert-'.$bg.'" data-tokens="'.$a['COD_FISC'][$y].'" value="'.$a['COD_FISC'][$y].'"';
								$ss.='>'.$a['COGNOME'][$y].' '.$a['NOME'][$y].' ('.$a['COD_FISC'][$y].')</option>';
							}
						}
					}
					$m.='<select name="docint" id="docint" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep ><option data-tokens=""></option>'.$ss.'</select>';
				$m.='</div>';
			$m.='</div><br />';

			if ($su or $ict){
				$m.='<div class="row bg-light py-1">';
					$m.='<div class="col-sm-4 text-right">Stato:</div>';
					$m.='<div class="col-sm-8">';
						$ss='';
						// "bozza","richiesta","validata_ict"
						$ss.='<option data-tokens="bozza" value="bozza">Bozza</option>';
						$ss.='<option data-tokens="richiesta" value="richiesta">Richiesta</option>';
						$ss.='<option data-tokens="validata_ict" value="validata_ict" selected>Validata ICT</option>';
						$m.='<select name="stato" id="stato" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep ><option data-tokens="0" value="0"></option>'.$ss.'</select>';
					$m.='</div>';
				$m.='</div><br />';
			}

		$m.='</form>';
		// get_upload_dialog($t='File upload',$m='',$l='carica file:',$accept="",$sact="",$getf="",$gett="")
		$s=get_upload_dialog($t,get_alert($m,'success').$w,'carica un file csv:','.csv,.txt',$tipo,'csv_default');
	}
	if (isset($_FILES)){			// chiamata per upload di file
		$_SESSION['IAM']['last_file_uploaded']='';
		if (!empty($_FILES)){
			$tit="<h2>Carica file</h2>"; $b=""; $m=''; $m1=''; $path='files/';
			try {
				$a=array_keys($_FILES);
				$aun=$a[0];
				$pnf=$path.$_FILES[$aun]['name'];		// path nome file target
			} catch(Throwable $e) {
				$m.=error_get_last();
			}
			if (!is_array($_FILES[$aun])){
				$m.="File non caricato correttamente";
			} else {
				$maxSize=5000000;
				$aea=array('csv','txt');
				if ($_FILES[$aun]['size'] > $maxSize and $maxSize > 0) {
					$smb=round($maxSize/1000000);
					$m.='<br><div class="alert alert-danger">Le dimensioni del file <strong>'.$_FILES[$aun]['name'].'</strong> eccedono il massimo di '.$smb.'Mb.</div>';
					// unlink($pnf);
				}
				$ext=strtolower(pathinfo($_FILES[$aun]['name'],PATHINFO_EXTENSION));
				if (!in_array($ext, $aea)){
					$m.='<br><div class="alert alert-danger">Puoi caricare solo file di tipo <strong>'.implode(', ',$aea).'</strong></div>';
				}
			}							
			if ($m == '' and $_FILES[$aun]['error'] == UPLOAD_ERR_OK) {
				try {
// https://www.php.net/manual/en/function.move-uploaded-file.php
// Warning If the destination file already exists, it will be overwritten.
					move_uploaded_file($_FILES[$aun]['tmp_name'], $pnf);
					$_SESSION['IAM']['last_file_uploaded']=$pnf;
				} catch(Throwable $e) {
					tolog(print_r(error_get_last(),true));
				}
				$m.=get_alert('il file: <strong>'.$_FILES[$aun]['name'].'</strong> di '.$_FILES[$aun]['size'].' byte &egrave; stato correttamente caricato.','success');
				$stl='bg-success text-center text-white';
				if (!empty($_REQUEST['sact'])){if ($_REQUEST['sact']=='adc'){$m1=aau_adc();}}
			} else {
				$m.=get_alert('il file: <strong>'.$_FILES[$aun]['name'].'</strong> di '.$_FILES[$aun]['size'].' byte <strong>NON</strong> &egrave; stato <strong>caricato.</strong>','danger');
				$stl='bg-danger text-center text-white';
			}
			$r=array('tit'=>$tit,'msg'=>$m.$m1,'stl'=>$stl,'btn'=>$b,'dom'=>'mm');
			$r['domm'][0]['domf']='ga_bottom';	$r['domm'][0]['domc']=get_ra_stato(); // default rileggo la tabella
			// file_put_contents('_tmp.log', print_r($r,true)."\r\n", LOCK_EX);
			// $s=json_encode($r);
			$s=safe_json_encode($r);
		}
	}	
	
	// file_put_contents('_tmp.log', $s."\r\n", FILE_APPEND | LOCK_EX);
	return $s;
}
function aau_adc(){								// dopo upload file csv degli account
	global $devel, $conn_sl;

	$nome='account';
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}	// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}	// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}	// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}	// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict

	if (empty($_SESSION['IAM']['last_file_uploaded'])){
		return get_alert('File non specificato','danger');
	}
	$nf=$_SESSION['IAM']['last_file_uploaded'];	// nome del file caricato

	// $uu='storico_ldap'; $pp='Ld4pSt0r1c0'; $cc='localhost'; 
	// $conn_sl = mysqli_connect($cc, $uu, $pp);		

	$s='';	// output
	if ($devel and $su){$s.=get_alert(json_encode($_REQUEST));}
	$e='';	// errori
	$w='';	// warnings
	$nome='';
	$cognome='';


	$nmr=50;	// numero massimo di righe da elaborare

	// ini_set('auto_detect_line_endings',TRUE);	
	$file = file_get_contents($nf);
	$acsv=explode(chr(10),$file); 				// chr(13) = fine riga (\r = CR = chr(13), \n = LF = chr(10))
	$sep=';';
	if (stripos($acsv[0],',') !== false){
		$sep=',';
	}
	if (stripos($acsv[0],';') !== false){
		$sep=';';
	}
	$campi=explode($sep,strtolower($acsv[0]));	// nomi delle colonne (prima riga csv)
	// $campi=array_filter($campi);				// rimuove gli elementi vuoti, gli zeri, i valori false e null.
	if (empty($campi)){
		return get_alert('File senza intestazioni');
	}
	// file_put_contents('_tmp.log',"righe: ".count($acsv)." - colonne: ".count($campi)." - sep: ".$sep."\r\n", LOCK_EX);

	// ripulisco i nomi delle colonne
	for ($i=0; $i<count($campi); $i++){
		if (!empty($campi[$i])){
			// file_put_contents('_tmp.log', "prima: ".$campi[$i], FILE_APPEND | LOCK_EX);
			// pone in minuscolo - filtra solo lettere minuscole, numeri, spazi e - (toglie "_' e altri crt non voluti)
			$campi[$i]=crt_filter(strtolower($campi[$i]),'§mi§nu§ -_');
			// file_put_contents('_tmp.log', " - dopo: ".$campi[$i]."\r\n", FILE_APPEND | LOCK_EX);
		}
	}

	// required
	$cf_exist = array_values(array_intersect($campi, array('cf','codice fiscale','codfis','cod_fis','cod_fisc','fiscal code','x-codicefiscale')));
	$cognome_exist = array_values(array_intersect($campi, array('cognome','surname','sn')));
	$nome_exist = array_values(array_intersect($campi, array('nome','name','givenname')));

	// optionals
	$mail_exist = array_values(array_intersect($campi, array('mail','email','e-mail','email_personale')));
	$ka_exist = array_values(array_intersect($campi, array('ka','afferenza','codice afferenza','struttura','dipartimento','cod struttura')));
	$kr_exist = array_values(array_intersect($campi, array('kr','ruolo','codice ruolo','categoria','cod categoria')));
	$resp_exist = array_values(array_intersect($campi, array('cf referente','referente')));
	$di_exist = array_values(array_intersect($campi, array('data inizio','inizio')));
	$df_exist = array_values(array_intersect($campi, array('data fine','fine')));

	// check required
	if (empty($cf_exist)){$e.=get_alert('manca il codice fiscale','danger',true);} else {$cf_exist=array_values($cf_exist);}
	if (empty($nome_exist)){$e.=get_alert('manca il nome','danger',true);} else {$nome_exist=array_values($nome_exist);}
	if (empty($cognome_exist)){$e.=get_alert('manca il cognome','danger',true);} else {$cognome_exist=array_values($cognome_exist);}
	if ($e != ''){
		return get_alert($e,'danger');
	}

	// file_put_contents('_tmp.log', json_encode($campi)."\r\n", FILE_APPEND | LOCK_EX);

	$csv=array();
	for ($i=0; $i<count($acsv); $i++){
	// foreach ($acsv as $csvi){	// per ogni riga
		// if (
			// !empty($acsv[$i]) 
			// and trim($acsv[$i]) != '' 
			// and $acsv[$i] != chr(10) 
			// and $acsv[$i] != chr(13) 
			// and $acsv[$i] != chr(13).chr(10)
			// and $acsv[$i] != chr(10).chr(13)
			// and 
			// strlen($acsv[$i]) > 17
			// ){
		if (strlen($acsv[$i]) < 18 and $i > 0){
			// file_put_contents('_tmp.log',"riga scartata: ".$i." |".$acsv[$i], FILE_APPEND | LOCK_EX);
			continue;
		}
			$csvii=implode(" ",explode("\n",$acsv[$i]));	// rimuovo i newline
			// aggiungo la riga a $csv
			// if (empty($csv)){
			if ($i==0){
				// intestazioni
				array_push($csv,$campi);	// metto l'array dei nomi che potrebbero essere cambiati dall'originale
			} else {
				$a=explode($sep,$csvii);
				// $a=array_filter($a);	// rimuove gli elementi vuoti, gli zeri, i valori false e null. (non devo farlo ... sulle righe è probabile ed ammesso)
				// file_put_contents('_tmp.log', json_encode($a)."\r\n", FILE_APPEND | LOCK_EX);
				if (count($a) == count($campi)){
					// il numero dei campi deve essere come nella riga intestazione altrimenti scarto la riga
					array_push($csv,$a);
				} else {
					// provo a concatenare questa con la precedente
					// file_put_contents('_tmp.log', "riga: ".$i." - ncr: ".count($a)." - nci: ".count($campi)."\r\n", FILE_APPEND | LOCK_EX);
					$csvii=implode(" ",explode("\r",implode(" ",explode("\n",$acsv[$i-1].$acsv[$i]))));	// rimuovo i nl ed i lf
					// file_put_contents('_tmp.log',"riga conc: ".$i." - ".$csvii."\r\n", FILE_APPEND | LOCK_EX);
					$a=explode($sep,$csvii);
					if (count($a) == count($campi)){
						array_push($csv,$a);
					} else {
						// file_put_contents('_tmp.log', "riga conc: ".$i." - ncr: ".count($a)." - nci: ".count($campi)."\r\n", FILE_APPEND | LOCK_EX);
					}
				}
			}
		// }
	}

	// file_put_contents('_tmp.log', print_r($csv,true)."\r\n", FILE_APPEND | LOCK_EX);

	// controlli
	if (count($csv) <= 1){	
		return get_alert('csv vuoto o con sola riga intestazione','danger');
	}

	$pi=array();
	$pi[$cf_exist[0]]=array_search($cf_exist[0],$campi);
	$pi[$cognome_exist[0]]=array_search($cognome_exist[0],$campi);
	$pi[$nome_exist[0]]=array_search($nome_exist[0],$campi);

	if (!empty($mail_exist)){$pi[$mail_exist[0]]=array_search($mail_exist[0],$campi);}
	if (!empty($ka_exist)){$pi[$ka_exist[0]]=array_search($ka_exist[0],$campi);}
	if (!empty($kr_exist)){$pi[$kr_exist[0]]=array_search($kr_exist[0],$campi);}
	if (!empty($resp_exist)){$pi[$resp_exist[0]]=array_search($resp_exist[0],$campi);}
	if (!empty($di_exist)){$pi[$di_exist[0]]=array_search($di_exist[0],$campi);}
	if (!empty($df_exist)){$pi[$df_exist[0]]=array_search($df_exist[0],$campi);}

	$icf=$pi[$cf_exist[0]];
	$inome=$pi[$nome_exist[0]];
	$icognome=$pi[$cognome_exist[0]];
	
	// file_put_contents('_tmp.log', "icf: ".$icf." ".$cf_exist[0]." - inome: ".$inome." ".$nome_exist[0]." - icognome: ".$icognome." ".$cognome_exist[0]."\r\n", FILE_APPEND | LOCK_EX);
	
	$cfgia=array(); 
	$rowok=array();
	array_push($rowok,0);
	for ($y=1; $y<count($csv) and $y < ($nmr + 1); $y++){	// loop sulle righe dati del csv (comincia da 1 ... la prima riga sono le intestazioni)
		$ee=''; $stl='';
		if (empty($csv[$y][$icf])){
			$ee.='Riga senza CF';
		}
		if ($ee==''){
			$cf=trim(crt_filter(strtoupper($csv[$y][$icf]),'§ma§nu'));	// pone in maiuscolo - filtra solo lettere maiuscole e numeri
			if (!empty($cfgia[$cf])){
				$ee.='CF duplicato in questo file (ignorato)';  $stl='info';
			}	// ho già un record buono per questo cf da questo file
		}
		if ($ee==''){
			$ee=ControllaCF(trim($cf)); // controllo formale CF
		}
		if ($ee==''){
			// controllo l'esistenza del CF nello storico richieste_account
			$sql="select * from storico_ldap.richieste_account where ra_cf='".$cf."'";
			$x=mysqli_query($conn_sl,$sql);
			if (!empty(mysqli_error($conn_sl))){return get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');}
			if (mysqli_num_rows($x) > 0) {	// dati trovati
				$ee="Codice fiscale esistente nello storico";
			}
		}
		if ($ee==''){
			//	stesso Cognome e nome = WARNING
			$nome=crt_filter(strtolower($csv[$y][$inome]),'§mi');	// pone in minuscolo - filtra solo lettere minuscole
			$cognome=crt_filter(strtolower($csv[$y][$icognome]),'§mi');	// pone in minuscolo - filtra solo lettere minuscole
		}
		if ($ee=='' and $nome != '' and $cognome != ''){
			$sql="select * from storico_ldap.richieste_account where lower(ra_cognome)='".$cognome."' and lower(ra_nome)='".$nome."'";
			$x=mysqli_query($conn_sl,$sql);
			if (!empty(mysqli_error($conn_sl))){return get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');}
			if (mysqli_num_rows($x) > 0) {	// dati trovati
				$ee="esiste nello storico un record con lo stesso nome e cognome (carica a mano)";	$stl='warning';
			}
		}
		if ($ee=='' and $nome != '' and $cognome != ''){
			// cerco eventuali nome || cognome che distano al massimo 1 crt in lunghezza
			$l=strlen($csv[$y][$icognome].$csv[$y][$inome]);
			$sql="select * from storico_ldap.richieste_account where 
			(      lower(ra_cognome) <>	'".$cognome."' 
				or lower(ra_nome)    <>	'".$nome."') 
			and
			(LENGTH (ra_cognome || ra_nome) >= " . ($l-1) . " and LENGTH (ra_cognome || ra_nome) <= " . ($l+1) . ")";
			$x=mysqli_query($conn_sl,$sql);
			if (!empty(mysqli_error($conn_sl))){return get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');}
			if (mysqli_num_rows($x) > 0) {	// dati trovati
				// su questi record posso valutare l'edit distance
				foreach($x as $xi){
					if (levenshtein($csv[$y][$icognome].$csv[$y][$inome],$xi['ra_cognome'].$xi['ra_nome']) < 3){
						$ee.='<li>esiste: '.$xi['ra_nome'].' '.$xi['ra_cognome']; $stl='warning';
					}
				}
			}
		}
		if ($ee=='' and $nome != '' and $cognome != ''){
			if (strlen($cognome) > 17){
				$ee.='<li>lunghezza cognome ('.$cognome.') di '.strlen($cognome).' crt eccede i 17 crt (lunghezza massima)'; $stl='warning';
			}
		}
		if ($ee==''){
			array_push($rowok,$y);
			$cfgia[$cf]=1;	// i prossimi CF uguali a questo saranno ignorati
		} else {
			// evidenzia l'errore
			if ($stl==''){$stl='danger';}
			$ee=implode(" ",explode('<br>',$ee));
			$csv[$y][$icf]='<div class="alert-'.$stl.'" title="'.$ee.'">'.trim($csv[$y][$icf]).'</div>'; 
			$csv[$y][$icognome]='<div class="alert-'.$stl.'" title="'.$ee.'">'.trim($csv[$y][$icognome]).'</div>';
			$csv[$y][$inome]='<div class="alert-'.$stl.'" title="'.$ee.'">'.trim($csv[$y][$inome]).'</div>';
		}
	}

	// file_put_contents('_tmp.log', "rowok: ".print_r($rowok,true)."\r\n", FILE_APPEND | LOCK_EX);
	// file_put_contents('_tmp.log', print_r($csv,true)."\r\n", FILE_APPEND | LOCK_EX);

	if (count($csv) > ($nmr + 1)){	
		$w.=get_alert('csv con '.count($csv).' righe<br />ho elaborato solo le prime '.$nmr.' righe','warning',true);
	}
	if ($w!=''){$s.=$w;}

	// file_put_contents('_tmp.log', "pi: ".print_r($pi,true)."\r\n", FILE_APPEND | LOCK_EX);

	$cols=array_keys($pi);	// array delle colonne trovate
	// file_put_contents('_tmp.log', "cols: ".print_r($cols,true)."\r\n", FILE_APPEND | LOCK_EX);
	
	$out=array();
	array_push($out,$cols);	// corretti
	// file_put_contents('_tmp.log', "out: ".print_r($out,true)."\r\n", FILE_APPEND | LOCK_EX);

	$outs=array();
	array_push($outs,$cols);	// scartati
	// file_put_contents('_tmp.log', "outs: ".print_r($outs,true)."\r\n", FILE_APPEND | LOCK_EX);

	for ($r=1; $r<count($csv) and $r < ($nmr + 1); $r++){
		$row=array();
		for ($c=0; $c<count($cols); $c++){
			$nc=$cols[$c];
			$i=$pi[$nc];
			$row[]=$csv[$r][$i];
		}
		if (in_array($r,$rowok)){
			array_push($out,$row);
		} else {
			array_push($outs,$row);
		}
	}

	// file_put_contents('_tmp.log', "out: ".print_r($out,true)."\r\n", FILE_APPEND | LOCK_EX);
	// file_put_contents('_tmp.log', "outs: ".print_r($outs,true)."\r\n", FILE_APPEND | LOCK_EX);

	if (!empty($e)){return $e;}
	
	$g=array();
	$d=array();
	if (!empty($cf_exist[0]))		{	$g[]=array($cf_exist[0]); 		$d[]=$cf_exist[0];		}
	if (!empty($cognome_exist[0]))	{	$g[]=array($cognome_exist[0]); 	$d[]=$cognome_exist[0];	}
	if (!empty($nome_exist[0]))		{	$g[]=array($nome_exist[0]); 	$d[]=$nome_exist[0];	}
	if (!empty($mail_exist[0]))		{	$g[]=array($mail_exist[0]); 	$d[]=$mail_exist[0];	}

	if (!empty($ka_exist[0]))		{	$g[]=array($ka_exist[0]); 		$d[]=$ka_exist[0];		}
	if (!empty($kr_exist[0]))		{	$g[]=array($kr_exist[0]); 		$d[]=$kr_exist[0];		}
	if (!empty($resp_exist[0]))		{	$g[]=array($resp_exist[0]); 	$d[]=$resp_exist[0];	}
	if (!empty($di_exist[0]))  		{	$g[]=array($di_exist[0]); 		$d[]=$di_exist[0];		}
	if (!empty($df_exist[0]))  		{	$g[]=array($df_exist[0]); 		$d[]=$df_exist[0];		}

	if (!empty($out)){
		$s1=get_table_data($out,$g,$d,'file_caricato',$t='csv',false);
		// file_put_contents('_tmp.log', 'tabella ok: '.$s1."\r\n", FILE_APPEND | LOCK_EX);
		$s.=$s1;
	}
	if (!empty($outs)){
		$s.=get_alert('Numero righe scartate = '.(count($outs) - 1),'danger');
		$s1=get_table_data($outs,$g,$d,'righe_scartate',$t='csv',false);
		// file_put_contents('_tmp.log', 'tabella ko: '.$s1."\r\n", FILE_APPEND | LOCK_EX);
		$s.=$s1;
	}
	
	// file_put_contents('_tmp.log', "out: ".print_r($out,true)."\r\n", FILE_APPEND | LOCK_EX);

	if (!empty($out)){
		// creazione dei record in bozza
		for ($r=1; $r<count($out); $r++){
			$sql="insert into storico_ldap.richieste_account (ra_tipo, ra_nome, ra_cognome, ra_cf, ra_mail_notifica, ra_ruolo, ra_afferenza, ra_d_afferenza, ra_inizio, ra_fine, ra_cf_referente, ra_nc_referente, ra_stato, ra_note, ra_nfcm, ra_usr_ins, ra_cancellabile) values (";
			$sql.="'PF'";																		// ra_tipo (PF, GE, GU)
			$inome=array_search($nome_exist[0],$cols);
			$sql.=",'".crt_filter(trim(ucwords(strtolower(trim(ab_str_utf8_ascii($out[$r][$inome]))))),'§ma§mi§nu. ')."'";		// ra_nome
			$icognome=array_search($cognome_exist[0],$cols);
			$sql.=",'".crt_filter(trim(ucwords(strtolower(trim(ab_str_utf8_ascii($out[$r][$icognome]))))),'§ma§mi§nu. ')."'";	// ra_cognome
			$icf=array_search($cf_exist[0],$cols);
			$sql.=",'".trim(crt_filter(strtoupper($out[$r][$icf]),'§ma§nu'))."'";				// ra_cf

			// file_put_contents('_tmp.log', "icf: ".$icf." ".$cf_exist[0]." - inome: ".$inome." ".$nome_exist[0]." - icognome: ".$icognome." ".$cognome_exist[0]."\r\n", FILE_APPEND | LOCK_EX);
			
			// ra_mail_notifica
			if (!empty($mail_exist)){
				$imail=array_search($mail_exist[0],$cols);
				$sql.=",'".trim($out[$r][$imail])."'";
			} else {
				$sql.=",null";
			}

			// ra_ruolo
			if (!empty($kr_exist)){
				$ikr=array_search($kr_exist[0],$cols);
				$sql.=",'".trim($out[$r][$ikr])."'";
			} else {
				if (empty($_REQUEST['kr'])){
					$sql.=",null";
				} else {
					$sql.=",'".trim($_REQUEST['kr'])."'";
				}
			}

			// ra_afferenza
			$kad='';
			if (!empty($ka_exist)){
				$ika=array_search($ka_exist[0],$cols);
				if ($ika !== false){
					$kad=$out[$r][$ika];
					$sql.=",'".trim($kad)."'";
				} else {
					$sql.=",null"; // codice afferenza specificato nel file non esistente (o non attivo)
				}
			} else {
				// non specificato nel file prendo quello indicato
				if (empty($_REQUEST['ka'])){
					$sql.=",null";
				} else {
					$kad=$_REQUEST['ka'];
					$sql.=",'".trim($kad)."'";
				}
			}
			
			// ra_d_afferenza
			$da='null';	// descrizione afferenza (necessaria per la creazione in AD)
			if (!empty($kad)){
				$ika=array_search($kad,$_SESSION['IAM']['lista_afferenze']['KA']);
				if ($ika !== false){
					$da="'".trim(substr(implode("''",explode("'",htmlentities(ab_str_utf8_ascii($_SESSION['IAM']['lista_afferenze']['DA'][$ika])))),0,60))."'";
				}
			}
			$sql.=",".$da;
						
			// ra_inizio
			if (!empty($di_exist)){
				$idi=array_search($di_exist[0],$cols);
				$sql.=",'".trim(convert_date($out[$r][$idi]))."'";		
			} else {
				if (empty($_REQUEST['di'])){
					$sql.=",'".date("Y-m-d")."'";
				} else {
					$sql.=",'".trim(convert_date($_REQUEST['di']))."'";
				}
			}

			// ra_fine 
			if (!empty($df_exist)){
				$idf=array_search($df_exist[0],$cols);
				$sql.=",'".trim(convert_date($out[$r][$idf]))."'";
			} else {
				if (empty($_REQUEST['df'])){
					// @@@@@@@@@@@@@ calcola 60 giorni (2 mesi)
					$nd=Date('d/m/Y', strtotime('+60 days'));
					$sql.=",'".trim(convert_date($nd))."'";		
				} else {
					$sql.=",'".trim(convert_date($_REQUEST['df']))."'";
				}
			}

			// ra_cf_referente
			$cfr='';
			if (!empty($resp_exist)){
				// specificato nel file di importazione
				$iresp=array_search($resp_exist[0],$cols);
				if ($iresp !== false){
					$cfr=$out[$r][$iresp];
				}
			} else {
				// rilevato dal dialogo di importazione
				if (!empty($_REQUEST['docint'])){
					$cfr=$_REQUEST['docint'];
				}
			}
			if (empty($cfr)){$sql.=",null";} else {$sql.=",'".trim($cfr)."'";}
			
			// ra_nc_referente
			$ncr='';
			if (!empty($cfr)){
				$icfr=array_search($cfr,$_SESSION['IAM']['ab_can']['COD_FISC']);
				if ($icfr !== false){
					$ncr=ucwords(strtolower(trim(substr(implode("''",explode("'",htmlentities($_SESSION['IAM']['ab_can']['COGNOME'][$icfr].' '.$_SESSION['IAM']['ab_can']['NOME'][$icfr]))),0,64))));
				}
			}
			if (empty($ncr)){$sql.=",null";} else {$sql.=",'".trim($ncr)."'";}

			if (empty($_REQUEST['stato'])){	// ra_stato
				$sql.=",'bozza'"; 															
			} else {
				$sql.=",'".trim($_REQUEST['stato'])."'"; 															
			}
			$sql.=",'".$nf."'";	// ra_nfcm (nome file caricamento massivo)
			$sql.=",'inserito tramite caricamento csv (".trim(end(explode('/',$nf))).")'";	// ra_note
			$sql.=",'".trim($_SESSION['IAM']['uid_login'])."'";						// ra_usr_ins
			$sql.=",1";																	// ra_cancellabile
			$sql.=")";
			// file_put_contents('_tmp.log', 'sql: '.$sql."\r\n", FILE_APPEND | LOCK_EX);
			// if ($devel and $su){$s.=get_alert($sql);}

			try {
				$x=mysqli_query($conn_sl,$sql);						
				if (!empty(mysqli_error($conn_sl))){
					$e=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');
				}
			} catch (Exception $ee) {
				$e=get_alert(($su?$sql.'<br />':'').'Exception: '.$ee->getMessage(),'danger');
			}

			if (!empty($e)){return $e;}

// insert into storico_ldap.richieste_account (ra_tipo, ra_nome, ra_cognome, ra_cf, ra_mail_notifica, ra_ruolo, ra_afferenza, ra_d_afferenza, ra_inizio, ra_fine, ra_cf_referente, ra_nc_referente, ra_stato, ra_note, ra_usr_ins, ra_cancellabile) values ('PF','Benedetta','Giambo'','GMBBDT04E61I726M',null,'CO1L','330322','Cl. Sc. Sociali - Scienze Poli','2023-09-19','2222-02-02','NRDMGR63A43G702Q','Nardi Mariagrazia','validata_ict','inserito tramite caricamento csv (SCIENZE SOCIALI vincitori allievi ordinari 23_24 - scienze politiche.csv)','a.bongiorni',1);	


		}			
	}
	// file_put_contents('_tmp.log', 's: '.$s."\r\n", FILE_APPEND | LOCK_EX);
	// file_put_contents('_tmp.log', 's: '.$s."\r\n", LOCK_EX);
		
	return $s;
}
/*
function manage_ldap($arr,$s='LDAP',$a='info'){		// @@@ operazioni LDAP / AD (usato per test)
	global $ldap_conn, $ad_conn;
	
//		s=server = default LDAP_TEST alternativa AD
//		a=azione = azione su LDAP o AD (crea, cambia, cancella, modifica, info, testa)
//			crea 						= crea nuovo account (solo LDAP) 
//			cambia 					= cambia password (solo LDAP) 
//			cancella				= cancella account (solo LDAP) 
//			modifica				= modifica account
//			info						= info account
//			testa						= testa la password
//			nuovo_guest			= nuovo id per guest account - getNextSerial("ou=GuestUsers,o=sss,c=it","x-seriale");
//			nuovo_uidnumber	=	- getNextSerial("cn=NextFreeUnixId,o=sss,c=it","uidNumber");
//			nuovo_gidnumber	= - getNextSerial("cn=NextFreeUnixId,o=sss,c=it","gidNumber");		
//		arr=array di attributi 
//			u				=	uid														= uid
//			cf																			= x-codicefiscale
//			mail																		= mail
//			nome																		= givenname
//			cognome																	= sn
//			p				=	password											= userpassword
//			kr			=	codice ruolo									= businesscategory
//			dr			=	descrizione ruolo							= employeetype
//			ka			=	codice afferenza							= departmentnumber
//			da			=	descrizione afferenza					= ou
//			fisso		=	telefono fisso								= telephonenumber
//			mobile	=	cellulare											= mobile
//			dtf			= data scadenza									= scadenzatempo
//			matricola																= employeenumber
	
	$aav=array('crea','cambia','cancella','modifica','info','testa','nuovo_guest','nuovo_uidnumber','nuovo_gidnumber');
	if ($s=='LDAP' or $s=='LDAP_TEST'){
		if (in_array($a, $aav)){$azione=$a;}
	}
	if ($s=='AD' or $s=='AD_TEST'){
		if ($a=='modifica' and !empty($arr)){$azione='modificaAD';}
		if ($a=='modifica' and !empty($arr)){$azione='modificaAD';}
	}
	if (empty($azione)){return;}
	
	switch ($azione) {
		case 'crea'; // OK

			// --- preparazione dati per LDAP
			$info=array();
			$dn='uid='.$arr['u'].',ou=Users,o=sss,c=it';
			$info["objectClass"][]="inetOrgPerson";
			$info["objectClass"][]="organizationalPerson";
			$info["objectClass"][]="posixAccount";
			$info["objectClass"][]="shadowAccount";
			$info["objectClass"][]="sambaSamAccount";
			$info["objectClass"][]="x-person";
			$info["objectClass"][]="radiusprofile";
			$info["objectClass"][]="person";
			$info["objectClass"][]="top";
			$info["objectClass"][]="schacEmployeeInfo";

			$info["uid"]=$arr['u'];
			$p=$arr['p'];
			$info["userPassword"]="$p"; // ok
//			$a=getNextSerial("ou=GuestUsers,o=sss,c=it","x-seriale"); // cattura next guest
			$a=getNextSerial("cn=NextFreeUnixId,o=sss,c=it","uidNumber");
			// $a=getNextSerial("sambaDomainName=SSS","sambaNextRid");
			$info["sambaSID"]="S-1-5-21-3475812762-2383038380-3056063006-".$a['n'];
			$info["sambaNTPassword"]=LMHash($arr['p']);
			$info["sambaAcctFlags"]="[UX]";
			$info["sambaKickoffTime"]="2147483647";
			// $info["sambaLMPassword"]="F3492FE33943AB35D6C10F1ED325FB21"; // obsoleto
			$info["sambaLogoffTime"]="2147483647";
			$info["sambaPwdMustChange"]="2147483647";
			$info["sambaLogonTime"]="0";
			$info["sambaPrimaryGroupSID"]="S-1-5-21-3475812762-2383038380-3056063006-513"; // vedi gidNumber
			$info["sambaPwdCanChange"]="0";
			$info["sambaPwdLastSet"]="0";
			$info["uidNumber"]=$a['n'].'';
			$info["homeDirectory"]="/home/nobody";  // nobody 
			// $info["displayName"]=$_REQUEST['ldap_sn']." ".$_REQUEST['ldap_givenname'];  
			$info["loginShell"]="/bin/nologin";  
			
			
			// Il seriale per gidNumber si usa quando si creano nuovi gruppi.
			// Nel nostro LDAP Per gli User è il valore costante 513  (514 per i Guest)
			
			// $a=getNextSerial("cn=NextFreeUnixId,o=sss,c=it","gidNumber");
			$info["gidNumber"]='513';	// $a['n'];  
			$info["gecos"]="User";  
			
			$info["sn"]=$arr['cognome'];
			$info["givenname"]=$arr['nome'];
			$info["cn"]=$arr['nome'].' '.$arr['cognome'];
			$info["description"]=$arr['nome'].' '.$arr['cognome'];
			$info["displayName"]=$arr['nome'].' '.$arr['cognome'];
			$info["mail"]=strtolower($arr['nome']).'.'.strtolower($arr['cognome']).'@santannapisa.it';
			$info["telephonenumber"]=$arr['fisso'];
			$info["mobile"]=$arr['mobile'];
			$info["x-codiceFiscale"]=$arr['cf'];
			$info["x-scadenzaTempo"]=$arr['dtf'].'220000.515Z';

			// $s.='<hr>'.print_r($info,true);
			// $j=json_encode($info);
			// $s.='<hr>'.$j;

			if ($s=='LDAP'){$r = ldap_add($ldap_conn, $dn, $info);}
			if ($s=='LDAP_TEST'){$r = ldap_add($ldap_conn, $dn, $info);}
			// $r = ldap_add_ext($ldap_conn, $info["dn"], $info);
			$e=get_ldap_error();if ($e!=''){$s.=$e;break;}
			$s.=get_alert('User <strong>'.$u.'</strong> creato','success');
			break;
		case 'creaAD'; // KO

// Info AD User giovanni.bongiorni - CN=Giovanni Bongiorni,OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it

// {"objectclass":{"count":4,"0":"top","1":"person","2":"organizationalPerson","3":"user"},"0":"objectclass","cn":{"count":1,"0":"Giovanni Bongiorni"},"1":"cn","sn":{"count":1,"0":"Bongiorni"},"2":"sn","givenname":{"count":1,"0":"Giovanni"},"3":"givenname","distinguishedname":{"count":1,"0":"CN=Giovanni Bongiorni,OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it"},"4":"distinguishedname","instancetype":{"count":1,"0":"4"},"5":"instancetype","whencreated":{"count":1,"0":"20210516175534.0Z"},"6":"whencreated","whenchanged":{"count":1,"0":"20210516175534.0Z"},"7":"whenchanged","displayname":{"count":1,"0":"Giovanni Bongiorni"},"8":"displayname","usncreated":{"count":1,"0":"289326572"},"9":"usncreated","usnchanged":{"count":1,"0":"289326577"},"10":"usnchanged","name":{"count":1,"0":"Giovanni Bongiorni"},"11":"name","objectguid":{"count":1,"0":"\u00a7\u0097\u00f1\u00a4\u001c~\u008cH\u00a7\u0087\u00c8\u0013Q9\u0013\u00a6"},"12":"objectguid","useraccountcontrol":{"count":1,"0":"66048"},"13":"useraccountcontrol","badpwdcount":{"count":1,"0":"0"},"14":"badpwdcount","codepage":{"count":1,"0":"0"},"15":"codepage","countrycode":{"count":1,"0":"0"},"16":"countrycode","badpasswordtime":{"count":1,"0":"0"},"17":"badpasswordtime","lastlogoff":{"count":1,"0":"0"},"18":"lastlogoff","lastlogon":{"count":1,"0":"0"},"19":"lastlogon","pwdlastset":{"count":1,"0":"132656613345916958"},"20":"pwdlastset","primarygroupid":{"count":1,"0":"513"},"21":"primarygroupid","objectsid":{"count":1,"0":"\u0001\u0005\u0000\u0000\u0000\u0000\u0000\u0005\u0015\u0000\u0000\u0000\u000e\u00e7\u00e6\u00d4\u00f1I\u00e7\u00f5u\u00cd\u008c\u00f6\u0085k\u0000\u0000"},"22":"objectsid","accountexpires":{"count":1,"0":"9223372036854775807"},"23":"accountexpires","logoncount":{"count":1,"0":"0"},"24":"logoncount","samaccountname":{"count":1,"0":"giovanni.bongiorni"},"25":"samaccountname","samaccounttype":{"count":1,"0":"805306368"},"26":"samaccounttype","userprincipalname":{"count":1,"0":"giovanni.bongiorni@sssapisa.it"},"27":"userprincipalname","objectcategory":{"count":1,"0":"CN=Person,CN=Schema,CN=Configuration,DC=sssapisa,DC=it"},"28":"objectcategory","dscorepropagationdata":{"count":1,"0":"16010101000000.0Z"},"29":"dscorepropagationdata","count":30,"dn":"CN=Giovanni Bongiorni,OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it"}

			// =====================================================
			// --- preparazione dati per AD
			$info_ad=array();
			$info_ad["objectClass"][]="top";
			$info_ad["objectClass"][]="person";
			$info_ad["objectClass"][]="organizationalPerson";
			$info_ad["objectClass"][]="user";
			$info_ad["cn"][]=$arr['nome'].' '.$arr['cognome'];
			$info_ad["sn"][]=$arr['cognome'];
			$info_ad["telephonenumber"][]=$arr['fisso'];
			$p=$arr['p'];
			$info_ad["userpassword"][]="$p";
			$info_ad["givenname"]=$arr['nome'];
			$info_ad["distinguishedname"]="CN=".$arr['nome'].' '.$arr['cognome'].",OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it"; // CN=Giovanni Bongiorni,OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it
			// instancetype
			// whencreated
			// whenchanged
			$info_ad["displayName"]=$arr['nome'].' '.$arr['cognome'];
			// usncreated
			// $info_ad["memberof"]="CN=ML_All,OU=DistributionList,OU=OpenLdap,DC=sssapisa,DC=it";
			// usnchanged
			$info_ad["name"]=$arr['nome'].' '.$arr['cognome'];; 
			// objectguid
			// useraccountcontrol
			// badpwdcount
			// codepage
			// countrycode
			// badpasswordtime
			// lastlogoff
			// lastlogon
			// pwdlastset
			$info_ad["primarygroupid"]="513";
			// objectsid

			list($usec, $sec) = explode(" ", microtime());
			$d = (((float)$usec + (float)$sec + 11644473600) * 10000000);
			$winInterval = round($d / 10000000);
			$unixTimestamp = ($winInterval - 11644473600);
			// $s.=get_alert('AD - Calcolo di accountexpires ad ora <strong>'.$d.'</strong> equivalente a '.date("d/m/Y H:i:s", $unixTimestamp),'warning');
			// $info_ad["accountexpires"]=$d; // @@@@@@@@@@@@@

			// logoncount
			$info_ad["samaccountname"]=strtolower($arr['nome'].'.'.$arr['cognome']);
			// samaccounttype
			$info_ad["userprincipalname"]=strtolower($arr['nome'].'.'.$arr['cognome'])."@sssapisa.it"; // @sssa.pisa.it @@@@@@
			
			$info_ad["objectcategory"]='CN=Person,CN=Schema,CN=Configuration,DC=sssapisa,DC=it'; // @@@@@@

			// dscorepropagationdata
			// lastlogontimestamp
			$info_ad["mobile"]="513";
			
if (false){
			$info_ad["title"]=""; // DA
			$info_ad["description"][0]=$arr['nome'].' '.$arr['cognome'];
			$info_ad["businesscategory"]=$arr['ka']; // KA
			$info_ad["physicaldeliveryofficename"]=$arr['da']; // DA
			$info_ad["telephonenumber"]=$arr['fisso'];
			$info_ad["userpassword"]=$arr['p']; // @@@@@
			$info_ad["givenname"]=$arr['nome'];
			// $info_ad["instancetype"]="4" // ????

			$info_ad["memberof"][]="CN=ListaAll_Test, OU=DistributionList, OU=ADprova, DC=sssapisa, DC=it";

//		"memberof": {
//			"0": "CN=ListaAll, OU=DistributionList, OU=OpenLdap, DC=sssapisa, DC=it",
//			"1": "CN=ML_AllConnettore, OU=DistributionList, OU=OpenLdap, DC=sssapisa, DC=it",
//			"2": "CN=ML_Migrati, OU=DistributionList, OU=OpenLdap, DC=sssapisa, DC=it",
//			"3": "CN=ict, OU=GruppiCGate, OU=OpenLdap, DC=sssapisa, DC=it",
//			"4": "CN=GRP_O365_ALL, OU=OpenLdap, DC=sssapisa, DC=it",
//			"5": "CN=GR_G_O_Non assegnato, CN=Users, DC=sssapisa, DC=it",
//			"6": "CN=GR_Istituto di Biorobotica, CN=Users, DC=sssapisa, DC=it",
//			"7": "CN=GR_G_T_Collaboratore, CN=Users, DC=sssapisa, DC=it"
//		},
//		
//		 // fare quando si crea Exchange
//		"proxyaddresses": {
//			"0": "smtp:M.Moscato@santannapisa.it",
//			"1": "SMTP:Marco.Moscato@santannapisa.it",
//			"2": "smtp:marco.moscato@alumnisssup.mail.onmicrosoft.com",
//			"3": "x500:/o=ExchangeLabs/ou=Exchange Administrative Group (FYDIBOHF23SPDLT)/cn=Recipients/cn=ba79cbf3331d4e76b6b6aaa3dd37a57d-Moscato Mar"
//		},

			$info_ad["employeenumber"]=""; // MATRICOLA
			$info_ad["homedirectory"]="/home/".$arr['u'];
			$info_ad["primarygroupid"]="513";
			
			
	// fare quando si crea Exchange
//		"showinaddressbook": {
//			"count": 3,
//			"0": "CN=All Recipients(VLV),CN=All System Address Lists,CN=Address Lists Container,CN=First Organization,CN=Microsoft Exchange,CN=Services,CN=Configuration,DC=sssapisa,DC=it",
//			"1": "CN=Default Global Address List,CN=All Global Address Lists,CN=Address Lists Container,CN=First Organization,CN=Microsoft Exchange,CN=Services,CN=Configuration,DC=sssapisa,DC=it",
//			"2": "CN=All Users,CN=All Address Lists,CN=Address Lists Container,CN=First Organization,CN=Microsoft Exchange,CN=Services,CN=Configuration,DC=sssapisa,DC=it"
//		},
//		"legacyexchangedn": {
//			"count": 1,
//			"0": "/o=First Organization/ou=Exchange Administrative Group (FYDIBOHF23SPDLT)/cn=Recipients/cn=ac220edebef44de3bfb41a6565f211f7-Bongi"
//		},

			$info_ad["mail"]=strtolower(implode('.',explode(' ',$arr['nome']))).'.'.strtolower(implode('.',explode(' ',$arr['cognome']))).'@santannapisa.it'; //
			$info_ad["carlicense"]='Y';	// S=modificato Y=nuovo
			$info_ad["departmentnumber"]=$arr['ka'];	// KA
			$info_ad["uidNumber"]=$a['n'].'';
			$info_ad["gecos"]='System User';
			$info_ad["mailnickname"]=$arr['u'];
			$info_ad["xscadenzatempo"]=$arr['dtf'].'200000.0Z'; // data fine
			$info_ad["xcodicefiscale"]=$arr['cf'];
 // fare quando si crea Exchange
//	"targetaddress": {
//		"count": 1,
//		"0": "SMTP:alberto.bongiorni@alumnisssup.mail.onmicrosoft.com"
//	},

			$info_ad["mobile"]=$arr['mobile'];
}			

			// "CN=Moscato Marco, OU=Istituto di Biorobotica, OU=OpenLdap, DC=sssapisa, DC=it"
			// "CN=Bongiorni Alberto,OU=Servizi ICT,OU=OpenLdap,DC=sssapisa,DC=it"
			// CN=Giovanni Bongiorni,OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it
			$dn_ad="CN=".$arr['nome'].' '.$arr['cognome'].",OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it";	
			
			// --- password
			// $info_ad["userPassword"]=LMHash($p);
			// $info_ad["adaccountpassword"]=LMHash($p);
			// $info_ad["unicodePwd"]="\"".LMHash($p)."\"";
			// $info_ad["userPassword"]=$p;
			// $newPassword = "\"" . $p . "\"";
      // $newPass = mb_convert_encoding($newPassword, "UTF-16LE");
      // $info_ad["unicodePwd"] = $newPass;
			// $info_ad["unicodePwd"]="\"".encodeToUtf8($p)."\"";
			$info_ad["unicodePwd"]=$arr['p'];

			// $j=json_encode($info_ad);
			// $s.=get_alert('Dati per AD di <strong>'.$u.'</strong><br /> '.$j.'<hr /><strong>DN</strong> = '.$dn_ad,'warning');

			// New-ADUser -Name "Jack Robinson" -GivenName "Jack" -Surname "Robinson" -SamAccountName "J.Robinson" -UserPrincipalName "J.Robinson@enterprise.com" -Path "OU=Managers,DC=enterprise,DC=com" -AccountPassword(Read-Host -AsSecureString "Input Password") -Enabled $true			

			// comando PowerShell per creazione account
			// $psc=';New-ADUser -Name "'.$info_ad["name"].'" -GivenName "'.$info_ad["givenname"].'" -Surname "'.$info_ad["sn"].'" -SamAccountName "'.$info_ad["samaccountname"].'" -UserPrincipalName "'.$info_ad["userprincipalname"].'" -Path "OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it" -AccountPassword (ConvertTo-SecureString "'.$p.'" -AsPlainText -force) -passThru -Enabled $True;';
			// $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			// $result = socket_connect($socket, '192.168.64.81', '4444');
			// $socketerror=socket_strerror(socket_last_error($socket));
			// $psc .= "Host: csidev\r\n";
			// $psc .= "Connection: Close\r\n\r\n";
			// $psc .= "quit\r\n\r\n";			
			// socket_write($socket, $psc, strlen($psc));
			// $psc = "quit\r\n";
			// socket_write($socket, $psc, strlen($psc));
			
			// rileggo per sapere se ha funzionato
			// $nc=$info_ad["userprincipalname"];
			// $sr = ldap_search($ad_conn, 'OU=U.O. Servizi Prova, OU=ADprova, DC=sssapisa, DC=it', "(userprincipalname=*$nc*)"); // cerco lo username
			// $e=get_ldap_error();if ($e!=''){$s.=$e; break;} // non l'ho trovato
			// $en = ldap_get_entries($ad_conn, $sr);	// leggo i dati trovati
			// $e=get_ldap_error();if ($e!=''){$s.=$e; break;}
			// $nen = ldap_count_entries($ad_conn, $sr);	// numero elementi trovati; dovrebbe essere solo 1
			// $e=get_ldap_error();if ($e!=''){$s.=$e; break;}
			// $j=safe_json_encode($en[0]);
			// $e=get_json_error();if ($e!=''){$s.=$e; break;}
			// $s.=get_alert('Info <strong>AD</strong> User <strong>'.$u.'</strong> - '.$en[0]['dn'].'<br><br>'.$j,'success');

			$r = ldap_add($ad_conn, $dn_ad, $info_ad);
			// $r = ldap_add_ext($ad_conn, $info["dn"], $info_ad);
			$e=get_ldap_error($ad_conn);if ($e!=''){$s.="<strong>ldap_add</strong><br />".$e; break;}
			$s.=get_alert('User <strong>'.$u.'</strong> creato','success');

			break;
		case 'cambia';	// cambia password OK
			// controlla esistenza
			$dn='uid='.$u.',ou=Users,o=sss,c=it';
			$user_search = ldap_search($ldap_conn,$dn,"(uid=".$u.")");
			$e=get_ldap_error();if ($e!=''){$s.=get_alert($dn.' '.$e,'danger');break;} else {$s.=get_alert($dn.' ldap_search','success');}
			$user_get = ldap_get_entries($ldap_conn, $user_search);
			$e=get_ldap_error();if ($e!=''){$s.=get_alert($dn.' '.$e,'danger');break;} else {$s.=get_alert($dn.' ldap_get_entries','success');}
			
			$entry = array();
			$p=$arr['p'];
			$entry["userPassword"] = "$p";
			ldap_modify($ldap_conn,$dn,$entry);
			$e=get_ldap_error();if ($e!=''){$s.=get_alert($dn.' '.$e,'danger');break;} else {$s.=get_alert($dn.' ldap_modify','success');}
			$s.=get_alert('Password cambiata per <strong>'.$u.'</strong>','success');

			break;
		case 'cambiaAD';	// KO cambia password
			// AD
			$debug=1;
			error_reporting(E_ALL);
			$user=strtolower($arr['u']);
			if ($debug) {$s.="<h2>TCP/IP Connection</h2>\n";}

			// Get the port for the WWW service. 
			$service_port = '4444';

			// Get the IP address for the target host. 
			$address = '192.168.64.81';

			// Create a TCP/IP socket.
			$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if ($socket === false) {
				$s.="socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
			} else {
				if ($debug) {$s.="OK.\n";}
			}

			if ($debug) {$s.="Attempting to connect to '$address' on port '$service_port'...";}
			$result = socket_connect($socket, $address, $service_port);
			if ($result === false) {
				$socketerror=socket_strerror(socket_last_error($socket));
				$s.="C'e' stato un problema col server Active Directory, si prega di contattare l'amministratore di sistema all'indirizzo: <a href='mailto:helpdesk@santannapisa.it'>helpdesk@santannapisa.it</a> <br> <i>Active directory server problem. Please contact your system administrator at <a href='mailto:helpdesk@santannapisa.it'>helpdesk@santannapisa.it</a></i>";
				// sendmail('helpdesk@santannapisa.it','Change password AD','socket_connect() failed. Reason: $socketerror ($result)',"From: CSI Scuola Sant'Anna <helpdesk@santannapisa.it>");
			} else {
				if ($debug) {$s.="AD ok.\n";}
			}
			$user=substr($user,0,20);
			$in = ";Set-ADAccountPassword -identity $u -NewPassword (ConvertTo-SecureString $p -AsPlainText -force) -Reset -PassThru;";
			$in .= "Host: csidev\r\n";
			$in .= "Connection: Close\r\n\r\n";
			$in .= "quit\r\n\r\n";
			$out = '';

			if ($debug) {$s.="Sending HTTP HEAD request...";}
			socket_write($socket, $in, strlen($in));
			if ($debug) {$s.="OK.\n";}

			if ($debug) {$s.="Reading response:\n\n";}
			while ($out = socket_read($socket, 2048)) {
				if ($debug) {$s.=$out;}
			}

			if ($debug) {$s.="Closing socket...";}
			$in = "quit\r\n";
			socket_write($socket, $in, strlen($in));
			//socket_close($socket);
			if ($debug) {$s.="OK.\n\n";}
			
			break;
		case 'cancella'; // OK cancella utente
			// controlla esistenza
			$dn='uid='.$arr['u'].',ou=Users,o=sss,c=it';
			$user_search = ldap_search($ldap_conn,$dn,"(uid=".$arr['u'].")");
			$e=get_ldap_error();if ($e!=''){$s.=$e;break;}
			$user_get = ldap_get_entries($ldap_conn, $user_search);
			$e=get_ldap_error();if ($e!=''){$s.=$e;break;}

			ldap_delete($ldap_conn,$dn);
			$e=get_ldap_error();if ($e!=''){$s.=$e;break;}
			$s.=get_alert('User <strong>'.$u.'</strong> cancellato','success');
			break;
		case 'modifica'; // OK modifica utente
			// controlla esistenza
			$dn='uid='.$arr['u'].',ou=Users,o=sss,c=it';
			$user_search = ldap_search($ldap_conn,$dn,"(uid=".$arr['u'].")");
			$e=get_ldap_error();if ($e!=''){$s.=$e;break;}
			$user_get = ldap_get_entries($ldap_conn, $user_search);
			$e=get_ldap_error();if ($e!=''){$s.=$e;break;}

			$entry = array();
			if (!empty($arr['cognome'])){$entry["sn"]=$arr['cognome'];}
			if (!empty($arr['nome'])){$entry["givenname"]=$arr['nome'];}
			if (!empty($arr['nome']) and !empty($arr['cognome'])){
				$entry["cn"]=$arr['nome'].' '.$arr['cognome'];
				$entry["description"]=$arr['nome'].' '.$arr['cognome'];
				$entry["displayName"]=$arr['nome'].' '.$arr['cognome'];
			}
			if (!empty($arr['mail'])){
				$entry["mail"]=$arr['mail'];
			} else {
				if (!empty($arr['nome']) and !empty($arr['cognome'])){
					$entry["mail"]=strtolower($arr['nome']).'.'.strtolower($arr['cognome']).'@santannapisa.it';
				}
			}
			if (!empty($arr['fisso'])){$entry["telephonenumber"]=$arr['fisso'];}
			if (!empty($arr['mobile'])){$entry["mobile"]=$arr['mobile'];}
			if (!empty($arr['cf'])){$entry["x-codiceFiscale"]=$arr['cf'];}
			if (!empty($arr['dtf'])){$entry["x-scadenzaTempo"]=$arr['dtf'].'220000.515Z';}
			
			$results = ldap_mod_replace($ldap_conn,$dn,$entry); // se ci sono
			$e=get_ldap_error();if ($e!=''){$s.=$e;break;}
			$s.=get_alert('User <strong>'.$u.'</strong> modificato','success');
			break;
		case 'modificaAD': // OK
			$a=array();
			// REQUEST
			// Array ( [ldap_uid] => giovanni.bongiorni [ldap_password] => ABek652wb [ldap_sn] => Bongiorni [ldap_givenname] => Giovanni [ldap_telephonenumber] => 883322 [ldap_mobile] => 3474247748 [ldap_cf] => BNGLRT57T20G702B [ldap_df] => 20220101 [func] => modificaAD [class] => btn btn-warning btn-sm btn-block [act] => modificaAD [dom] => out_a [tab] => a )

			// mi autentico come "User Read"
			$ldapBind = ldap_bind($ad_conn,'sssapisa.it/OpenLdap/Servizi ICT/User Read','readuser'); // ok
			$e=get_ldap_error();if ($e!=''){$s.=$e;} // non mi sono autenticato
			$nc=strtolower($arr['nome'].'.'.$arr['cognome']).'@sssapisa.it';
			$sr = ldap_search($ad_conn, 'OU=U.O. Servizi Prova, OU=ADprova, DC=sssapisa, DC=it', "(userprincipalname=*$nc*)"); // cerco lo username
			$e=get_ldap_error();if ($e!=''){$s.=$e; break;} // non l'ho trovato
			$en = ldap_get_entries($ad_conn, $sr);	// leggo i dati trovati
			$e=get_ldap_error();if ($e!=''){$s.=$e; break;}
			$nen = ldap_count_entries($ad_conn, $sr);	// numero elementi trovati; dovrebbe essere solo 1
			$e=get_ldap_error();if ($e!=''){$s.=$e; break;}

			if (!empty($arr['fisso'])){$a['telephonenumber']=$arr['fisso'];}
			if (!empty($arr['mobile'])){$a['mobile']=$arr['mobile'];}
			
			$arr['ad_dn']=$en[0]['dn'];

			ldap_mod_replace($ad_conn,$arr['ad_dn'],$a);
			$e=get_ldap_error('AD','ldap_mod_replace<br>');if ($e!=''){$s.=$e;}
			if ($e!=''){
				$s.=get_alert('Modifica AD <strong>'.$arr['ldap_uid'].'</strong> NON modificato<br />','danger');
			} else {
				$s.=get_alert('Modifica AD <strong>'.$arr['ldap_uid'].'</strong> modificato','success');
			}

			break;
		case 'info'; // OK
			// error_reporting(E_ALL);
			// LDAP (uid=a.bongiorni,ou=Users,o=sss,c=it)
			$dn='uid='.$arr['u'].',ou=Users,o=sss,c=it';
			$user_search = ldap_search($ldap_conn,$dn,"(uid=".$arr['u'].")");
			$e=get_ldap_error();if ($e!=''){$s.=$e;}
			$user_get = ldap_get_entries($ldap_conn, $user_search);
			$e=get_ldap_error();if ($e!=''){$s.=$e;}
			// $j=json_encode($user_get);
			$j=safe_json_encode($user_get);
			$e=get_json_error();if ($e!=''){$s.=$e;}
			$s.=get_alert('Info <strong>LDAP</strong> User <strong>'.$u.'</strong> - '.$dn.'<br><br>'.$j,'success');

			break;
		case 'infoAD'; // OK
		
			// mi autentico come "User Read"
			$ldapBind = ldap_bind($ad_conn,'sssapisa.it/OpenLdap/Servizi ICT/User Read','readuser'); // ok
			$e=get_ldap_error();if ($e!=''){$s.=$e;} // non mi sono autenticato
			$nc=strtolower($arr['nome'].'.'.$arr['cognome']).'@sssapisa.it';
//			$sr = ldap_search($ad_conn, 'OU=U.O. Servizi Prova, OU=ADprova, DC=sssapisa, DC=it', "(userprincipalname=*$u*)"); // cerco lo username
			$sr = ldap_search($ad_conn, 'OU=U.O. Servizi Prova, OU=ADprova, DC=sssapisa, DC=it', "(userprincipalname=*$nc*)"); // cerco lo username
			$e=get_ldap_error();if ($e!=''){$s.=$e; break;} // non l'ho trovato

			$en = ldap_get_entries($ad_conn, $sr);	// leggo i dati trovati
			$e=get_ldap_error();if ($e!=''){$s.=$e; break;}
			$nen = ldap_count_entries($ad_conn, $sr);	// numero elementi trovati; dovrebbe essere solo 1
			$e=get_ldap_error();if ($e!=''){$s.=$e; break;}

			$j=safe_json_encode($en[0]);
			$e=get_json_error();if ($e!=''){$s.=$e; break;}
			$s.=get_alert('Info <strong>AD</strong> User <strong>'.$u.'</strong> - '.$en[0]['dn'].'<br><br>'.$j,'success');
			
			break;
		case 'testa'; // KO
			$dn='uid='.$arr['u'].',ou=Users,o=sss,c=it';
			$tf=ldap_bind($ldap_conn, $dn, $p);
			$e=get_ldap_error();
			if ($e!=''){
				$s.=$e;
				$s.=get_alert('Password LDAP - <strong>Errata</strong>','danger');
			} else {
				$s.=get_alert('Password LDAP - <strong>Corretta</strong>','success');
			}
			break;
			
		case 'testaAD'; // OK
			// bind di un utente: $ldapBind = ldap_bind($ad_conn,'sssapisa.it/OpenLdap/Servizi ICT/Bongiorni Alberto','ABek652wb');	// ok
			// CN=Giovanni Bongiorni,OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it
			$ldapBind = ldap_bind($ad_conn,'sssapisa.it/OpenLdap/Servizi ICT/User Read','readuser');	// ok
			$e=get_ldap_error();if ($e!=''){$s.=$e;break;}
			$u=$arr['u'];
			$sr = ldap_search($ad_conn, 'OU=U.O. Servizi Prova, OU=ADprova, DC=sssapisa, DC=it', "(userprincipalname=*$u*)"); // giovanni.bongiorni@sssapisa.it
			$e=get_ldap_error();if ($e!=''){$s.=$e;break;}
			$en = ldap_get_entries($ad_conn, $sr);
			$e=get_ldap_error();if ($e!=''){$s.=$e;break;}
			$nen = ldap_count_entries($ad_conn, $sr);			
			$e=get_ldap_error();if ($e!=''){$s.=$e;break;}
			for ($i=0; $i<$nen; $i++) {
				$upn=$en[$i]["userprincipalname"][0];				
				if ($upn==$u.'@sssapisa.it') {
					$dn=$en[$i]["dn"];
					$ldapBind = ldap_bind($ad_conn,$dn,$p);
					if ($ldapBind) {
						$j=safe_json_encode($en);
						$s.=get_alert('Password AD <strong>Corretta</strong>'.'<br />'.$j,'success');
					} else {
						$s.=get_alert('Password AD <strong>Errata</strong>','danger');
					}
				}
			}
			break;
			
		case 'nuovo_guest';
			$a=getNextSerial("ou=GuestUsers,o=sss,c=it","x-seriale");
			$s.=$a['msg'];
			break;
		case 'nuovo_uidnumber';
			$a=getNextSerial("cn=NextFreeUnixId,o=sss,c=it","uidNumber");
			$s.=$a['msg'];
			break;
		case 'nuovo_gidnumber';
			$a=getNextSerial("cn=NextFreeUnixId,o=sss,c=it","gidNumber");
			$s.=$a['msg'];
			break;
		case '';
			break;
	}	
}
*/
function get_pdf_account($row){
	/*
		$pdf_nc			nome e cognome
		$pdf_uid		uid
		$pdf_psw		password
		$pdf_alt		mail alternativa
		
		get_pdf_account($nome.' '.$cognome,$j[0]['ra_uid'],$j[0]['ra_prima_password'],$j[0]['ra_ad_princ_mail']);
		
		ucwords(strtolower(trim(ab_str_utf8_ascii($_REQUEST['cognome']))))
	*/
	$pdf_nc=ucwords(strtolower(trim($row['ra_nome']).' '.trim($row['ra_cognome'])));
	$pdf_uid=$row['ra_uid'];
	$pdf_psw=$row['ra_prima_password'];
	$pdf_prm=$row['ra_ad_princ_mail'];
	$pdf_alt=$row['ra_uid'].'@santannapisa.it';
	
	class PDF extends FPDF {
		// Page header
		function Header() {
			// Logo
			$this->Image('logo.png',10,6,70);
			// Arial bold 15
			$this->SetFont('Arial','B',15);
			// Move to the right
			$this->Cell(140);
			// Title
			$this->SetTextColor(196,18,48);
			$this->Cell(30,10,'ICT Service',0,1,'C');
			// Line break
			$this->Ln(20);
		}

		// Page footer
		function Footer() {
			// Position at 1.5 cm from bottom
			$this->SetY(-15);
			// Arial italic 8
			$this->SetFont('Arial','I',8);
			// Page number
			$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
		}
	}

	// Instanciation of inherited class
	$pdf = new PDF();
	$pdf->AliasNbPages();

	// 1' pagina
	$pdf->AddPage();

		// Linea (hr)
		$pdf->SetDrawColor(196,18,48);
		$pdf->Line(10, 30, 200, 30);
		$pdf->Ln(5);

		$pdf->SetFont('Times','B',22);
		$pdf->Cell(200,20,$pdf_nc,0,1,'C');	// nome e cognome
		$pdf->SetFont('');

		// Linea (hr)
		$pdf->Line(10, 80, 200, 80);
		$pdf->Ln(5);

	// 2' pagina
	$pdf->AddPage();

		$pdf->Line(10, 60, 200, 60);

		$pdf->Ln(25);
		$pdf->SetFont('Times','B',12);
		$pdf->Cell(200,10,'Account Informations',0,1,'C');
		$pdf->SetFont('');

		$pdf->SetTextColor(196,18,48);
		$pdf->Cell(25);
		$pdf->Cell(50,8,'Account: ','TL',0,'R');
		$pdf->Cell(100,8,$pdf_uid,'TR',1);

		$pdf->Cell(25);
		$pdf->Cell(50,8,'Password: ','L',0,'R');
		$pdf->Cell(100,8,$pdf_psw,'R',1);

		$pdf->Cell(25);
		$pdf->Cell(50,8,'Principal email: ','L',0,'R');
		$pdf->Cell(100,8,$pdf_prm,'R',1);

		$pdf->Cell(25);
		$pdf->Cell(50,8,'Alternate email: ','BL',0,'R');
		$pdf->Cell(100,8,$pdf_alt,'BR',1);

		$pdf->Ln();

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Times','B',12);
		$pdf->Cell(200,5,'Please, change your password starting from tomorrow to access the active directory services:',0,1,'C');
		$pdf->SetFont('');
		$pdf->Cell(200,5,'https://www.santannapisa.it/password/',0,1,'C');

		$pdf->Ln(5);

		$pdf->SetFont('Times','B',12);
		$pdf->Cell(200,10,'E-Mail Client Configuration Informations',0,1,'C');
		$pdf->SetFont('');

		$pdf->SetTextColor(196,18,48);
		$pdf->Cell(25);
		$pdf->Cell(50,8,'Mail Server: ','TL',0,'R');
		$pdf->Cell(100,8,'oulook.office365.com','TR',1);

		$pdf->Cell(25);
		$pdf->Cell(50,8,'Outgoing Server: ','L',0,'R');
		$pdf->Cell(100,8,'smtp.office365.com','R',1);

		$pdf->Cell(25);
		$pdf->Cell(50,8,'Office 365 URL: ','L',0,'R');
		$pdf->Cell(100,8,'https://portal.office.com','R',1);

		$pdf->Cell(25);
		$pdf->Cell(50,8,'User: ','L',0,'R');
		$pdf->Cell(100,8,$pdf_prm,'R',1);

		$pdf->Cell(25);
		$pdf->Cell(50,8,'Password: ','BL',0,'R');
		$pdf->Cell(100,8,'Your password','BR',1);

		$pdf->Ln(5);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Times','B',12);
		$pdf->Cell(200,10,'Web-Mail Access',0,1,'C');
		$pdf->SetFont('');

		$pdf->SetTextColor(196,18,48);
		
		$pdf->Cell(25);
		$pdf->Cell(50,8,'Host: ','TL',0,'R');
		$pdf->Cell(100,8,'https://outlook.office.com/','TR',1);
		
		$pdf->Cell(25);
		$pdf->Cell(50,8,'User: ','L',0,'R');
		$pdf->Cell(100,8,$pdf_prm,'R',1);

		$pdf->Cell(25);
		$pdf->Cell(50,8,'Password: ','BL',0,'R');
		$pdf->Cell(100,8,'Your password','BR',1);

		$pdf->Ln();
		$pdf->Line(10, 225, 200, 225);

		$pdf->SetTextColor(0,0,0);	// RESET FONT COLOR NERO

/* 
string Output([string dest [, string name [, boolean isUTF8]]])

Parametri
	dest
		Destinazione dove mandare il documento. Può essere specificato uno dei seguenti valori:
			I: manda il 'file inline' al browser. Il plug-in sarà utilizzato se presente.
			D: manda al browser e forza il download del file con il nome dato con name.
			F: salva il file in locale con il nome dato con name.
			S: ritorna il documento come stringa.
			Il valore predefinito è I.
	name
		Il nome del file. È ignorato nel caso di destinazione S.
		Il valore predefinito è doc.pdf.
	isUTF8
		Indica se name segue la codifica ISO-8859-1 (false) o UTF-8 (true). Usato solo per le destinazioni I e D.
		Il valore predefinito è false.
*/
	$nfe='pdf/'.$pdf_uid.'.pdf';	// path del file pdf
	$pdf->Output('F',$nfe);
	$nfel='pdf/'.$pdf_uid.'.pdf';										// nome del file pdf
	return $nfel;
}
function get_pdf_account_guest($row){ 		// @@@
	/*
		$pdf_nc			nome e cognome
		$pdf_uid		uid
		$pdf_psw		password
		$pdf_alt		mail alternativa
		get_pdf_account_guest($nome,$cognome,$j[0]['ra_uid'],$j[0]['ra_prima_password'],$j[0]['ra_fine'],$j[0]['ra_note'],$j[0]['ra_ad_princ_mail'],$j[0]['ra_cf']);
	
	*/
	$pdf_nome=$row['ra_nome'];
	$pdf_cognome=$row['ra_cognome'];
	$pdf_uid=$row['ra_uid'];
	$pdf_psw=$row['ra_prima_password'];
	$pdf_scadenza=$row['ra_fine'];
	$pdf_evento=$row['ra_note'];
	$pdf_alt=$row['ra_ad_princ_mail'];
	$pdf_cf=$row['ra_cf'];
	
	class PDF extends FPDF {
		// Page header
		// Scuola Superiore Sant'Anna
		// Credenziali utente ospite/Guest user credentials
		function Header() {
			// Logo
			$this->Image('logo.png',10,6,70);
			// Arial bold 15
			$this->SetFont('Arial','B',15);
			// Move to the right
			$this->Cell(140);
			// Title
			$this->SetTextColor(196,18,48);
			$this->Cell(30,10,'ICT Service',0,1,'C');
			// Line break
			$this->Ln(20);
		}

		// Page footer
		function Footer() {
			// Position at 1.5 cm from bottom
			$this->SetY(-15);
			// Arial italic 8
			$this->SetFont('Arial','I',8);
			// Page number
			$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
		}
	}

	// Instanciation of inherited class
	$pdf = new PDF();
	$pdf->AliasNbPages();

	// 1' pagina
	$pdf->AddPage();

		// Linea (hr)
		$pdf->SetDrawColor(196,18,48);
		$pdf->Line(10, 30, 200, 30);
		$pdf->Ln(5);

		$pdf->SetFont('Times','B',22);
		$pdf->Cell(200,20,$pdf_nome.' '.$pdf_cognome,0,1,'C');
		$pdf->SetFont('');
		// Nome/Name: Alessio
		// Cognome/Surname: Campostrini

		// Linea (hr)
		$pdf->Line(10, 80, 200, 80);
		$pdf->Ln(5);

	// 2' pagina
	$pdf->AddPage();

		$pdf->Line(10, 60, 200, 60);

		$pdf->Ln(25);
		$pdf->SetFont('Times','B',12);
		$pdf->Cell(200,10,'Account Information',0,1,'C');
		$pdf->SetFont('');

		$pdf->SetTextColor(196,18,48);
		// Nome utente/Username: g30282
		$pdf->Cell(25);
		$pdf->Cell(50,8,'Account: ','TL',0,'R');
		$pdf->Cell(100,8,$pdf_uid,'TR',1);

		// Password: 82264180
		$pdf->Cell(25);
		$pdf->Cell(50,8,'Password: ','L',0,'R');
		$pdf->Cell(100,8,$pdf_psw,'R',1);

		// Scadenza anno-mese-giorno/Expiry year-month-day: 2021-12-12
		// Creazione anno-mese-giorno/Creation year-month-day: 2021-11-11
		// Identificazione o evento/Identification or event: Consulenza AD

		// Email:
		//	$pdf->Cell(25);
		//	$pdf->Cell(50,8,'alternate email: ','BL',0,'R');
		//	$pdf->Cell(100,8,$pdf_alt,'BR',1);

		// Codice fiscale/Tax code:

		// $pdf->Ln();

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Times','B',12);
		$pdf->Cell(200,5,'Please, change your password starting from tomorrow to access the active directory services:',0,1,'C');
		$pdf->SetFont('');
		$pdf->Cell(200,5,'https://www.santannapisa.it/password/',0,1,'C');

		$pdf->Ln(5);

		$pdf->SetFont('Times','B',12);
		$pdf->Cell(200,10,'E-Mail Client Configuration Information',0,1,'C');
		$pdf->SetFont('');

		$pdf->SetTextColor(196,18,48);
		$pdf->Cell(25);
		$pdf->Cell(50,8,'Mail Server: ','TL',0,'R');
		$pdf->Cell(100,8,'oulook.office365.com','TR',1);

		$pdf->Cell(25);
		$pdf->Cell(50,8,'Outgoing Server: ','L',0,'R');
		$pdf->Cell(100,8,'smtp.office365.com','R',1);

		$pdf->Cell(25);
		$pdf->Cell(50,8,'WebMail URL: ','BL',0,'R');
		$pdf->Cell(100,8,'https://portal.office.com','BR',1);		
		
		$pdf->Ln();
		$pdf->Line(10, 160, 200, 160);

		$pdf->SetTextColor(0,0,0);

/* 
string Output([string dest [, string name [, boolean isUTF8]]])

Parametri
	dest
		Destinazione dove mandare il documento. Può essere specificato uno dei seguenti valori:
			I: manda il 'file inline' al browser. Il plug-in sarà utilizzato se presente.
			D: manda al browser e forza il download del file con il nome dato con name.
			F: salva il file in locale con il nome dato con name.
			S: ritorna il documento come stringa.
			Il valore predefinito è I.
	name
		Il nome del file. È ignorato nel caso di destinazione S.
		Il valore predefinito è doc.pdf.
	isUTF8
		Indica se name segue la codifica ISO-8859-1 (false) o UTF-8 (true). Usato solo per le destinazioni I e D.
		Il valore predefinito è false.
*/

	$nfe='/var/www/html/ldap/pdf/'.$pdf_uid.'.pdf';
	$pdf->Output('F',$nfe);
	$nfel='pdf/'.$pdf_uid.'.pdf';
	return $nfel;
}
function ricrea_storico(){				// trasferita in ricrea_storico_ldap.php
	$a=array();
	$a['tit']='Ricrea Storico';
	$a['msg']='<a target="_blank" href="https://iam.local.santannapisa.it/ricrea_storico_ldap.php" class="btn btn-success btn-block">Ricrea storico</a>';
	$a['stl']='';
	$a['btn']='';
	$a['dom']='mm';
	$s=json_encode($a);
	return $s;	
// ------------------------------------------------------
/*
	global $conn_sl;
	$s='';
	// ricostruzione nuovo storico account da quello vecchio
	// cancello gli alias degli storicizzati
	$sql="delete from storico_ldap.richieste_account_alias where raa_ra_k in (select distinct ra_k from storico_ldap.richieste_account where ra_stato='storicizzata')";
	$a=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){$s.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');} else {$s.=get_alert($sql);}
	// cancello i log degli storicizzati
	$sql="delete from storico_ldap.richieste_account_log where ral_ra_k in (select distinct ra_k from storico_ldap.richieste_account where ra_stato='storicizzata')";
	$a=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){$s.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');} else {$s.=get_alert($sql);}
	// cancello gli storicizzati
	$sql="delete from storico_ldap.richieste_account where ra_stato='storicizzata'";
	$a=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){$s.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');} else {$s.=get_alert($sql);}
	// inserisco nel nuovo storico gli account del vecchio storico
	$sql="insert into storico_ldap.richieste_account (ra_tipo, ra_nome, ra_cognome, ra_mail, ra_cf, ra_mail_notifica, ra_notificata, ra_dt_notifica, ra_ruolo, ra_afferenza, ra_inizio, ra_fine, ra_cf_referente, ra_nc_referente, ra_stato, ra_motivo, ra_uid, ra_aliases, ra_redirect, ra_prima_password, ra_note, ra_usr_ins) select 'PF' as tipo, tblAnagrafe.nome, tblAnagrafe.cognome, concat(concat(concat(LOWER(tblAnagrafe.nome), '.'), LOWER(tblAnagrafe.cognome)), '@santannapisa.it') as mail, null as cf, null as mail_notifica, 0 as notificata, null as dt_notifica, null as ruolo, null as afferenza, from_unixtime(tblMailAccount.apertura, '%Y-%m-%d') as inizio, from_unixtime(tblMailAccount.chiusura,  '%Y-%m-%d') as fine, null as cf_referente, null as nc_referente, 'storicizzata' as stato, null as motivo, tblMailAccount.nome as uid, null as aliases, tblMailAccount.redirezione as redirect, tblMailAccount.password as prima_password, concat(concat(tblGID.nome, ' '), tblGID.descrizione) as note, 'old_storico' as usr_ins from tblMailAccount left join tblAnagrafe on tblMailAccount.idAnagrafe = tblAnagrafe.id left join tblGID on tblAnagrafe.idGID = tblGID.id";
	$a=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){$s.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');} else {$s.=get_alert($sql);}

	// alias
	// $sql="delete from storico_ldap.richieste_account_alias";
	// $a=mysqli_query($conn_sl,$sql);
	// if (!empty(mysqli_error($conn_sl))){$s.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');} else {$s.=get_alert($sql);}
	// inserisco negli alias del nuovo storico gli alias del vecchio storico
	$sql="insert into storico_ldap.richieste_account_alias (raa_ra_k, raa_ra_uid, raa_uid, raa_usr_ins) select 	richieste_account.ra_k, richieste_account.ra_uid, tblAlias.nome as alias, 'old_storico' as usr_ins from tblAlias join tblMailAccount on tblMailAccount.id = tblAlias.idMailAccount join richieste_account on tblMailAccount.nome = richieste_account.ra_uid";
	$a=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){$s.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');} else {$s.=get_alert($sql);}

	// aggiorno ra_aliases in richieste_account inserendo la lista degli alias di ogni account
	$sql="select raa_uid, raa_ra_k from storico_ldap.richieste_account_alias";
	$a=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){$s.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');} else {$s.=get_alert($sql);}
	$i=0;
	foreach($a as $row){
		$i++;
		$ab=array();
		$sql="select ra_aliases from storico_ldap.richieste_account where ra_k=".$row['raa_ra_k'];
		// if (fmod($i,3000)==0){$s.=get_alert($sql);}
		$b=mysqli_query($conn_sl,$sql);
		if (!empty(mysqli_error($conn_sl))){$s.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');} 
		if (mysqli_num_rows($b) > 0) {	// dati trovati
			foreach($b as $r){$ab[]=$r;}
		}
		if (count($ab) > 0){
			$sa=$ab[0]['ra_aliases'];
			if ($sa != ''){$sa.='|';}
			$sa.=$row['raa_uid'];
			$sql="update storico_ldap.richieste_account set ra_aliases='".$sa."'";
			if (strtolower($ab[0]['ra_nome'].'.'.$ab[0]['ra_cognome']) == strtolower($row['raa_uid'])){
				$sql.=", ra_ad_princ_mail='".$row['raa_uid']."'";
				$sql.=", ra_ad_motivo='recuperata da storico = nome.cognome'";
			}
			$sql.=" where ra_k=".$row['raa_ra_k'];
			// if (fmod($i,3000)==0){$s.=get_alert($sql);}
			$b=mysqli_query($conn_sl,$sql);
			if (!empty(mysqli_error($conn_sl))){$s.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');}
		}
	}
	
	// riporto i codici fiscali conosciuti (attivi -> ab_can)
	if (!empty($_SESSION['IAM']['ab_can'])){
		for ($i=0; $i < count($_SESSION['IAM']['ab_can']['LDAP_UID']); $i++) {
			if ($_SESSION['IAM']['ab_can']['LDAP_UID'][$i] != ''){
				$sql="update storico_ldap.richieste_account set";
				$sql.=" ra_cf='".$_SESSION['IAM']['ab_can']['COD_FISC'][$i]."'";
				if (!empty($_SESSION['IAM']['ab_can']['KR'][$i])){$sql.=", ra_ruolo='".$_SESSION['IAM']['ab_can']['KR'][$i]."'";}
				if (!empty($_SESSION['IAM']['ab_can']['KA'][$i])){$sql.=", ra_afferenza='".$_SESSION['IAM']['ab_can']['KA'][$i]."'";}
				$sql.=" where ra_uid='".$_SESSION['IAM']['ab_can']['LDAP_UID'][$i]."'";
				$b=mysqli_query($conn_sl,$sql);
				if (!empty(mysqli_error($conn_sl))){$s.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');}
			}
		}
	}
	// riporto i codici fiscali conosciuti (scaduti -> ab_csn) se non già popolati
	if (!empty($_SESSION['IAM']['ab_csn'])){
		for ($i=0; $i < count($_SESSION['IAM']['ab_csn']['LDAP_UID']); $i++) {
			if ($_SESSION['IAM']['ab_csn']['LDAP_UID'][$i] != ''){
				$sql="update storico_ldap.richieste_account set ra_cf = if (ra_cf is null, '".trim($_SESSION['IAM']['ab_csn']['COD_FISC'][$i])."', ra_cf)";
				if (!empty($_SESSION['IAM']['ab_csn']['KR'][$i])){$sql.=", ra_ruolo = if (ra_ruolo is null, '".$_SESSION['IAM']['ab_csn']['KR'][$i]."', ra_ruolo)";}
				if (!empty($_SESSION['IAM']['ab_csn']['KA'][$i])){$sql.=", ra_afferenza = if (ra_afferenza is null, '".$_SESSION['IAM']['ab_csn']['KA'][$i]."', ra_afferenza)";}
				$sql.=" where ra_uid='".$_SESSION['IAM']['ab_csn']['LDAP_UID'][$i]."'";
				$b=mysqli_query($conn_sl,$sql);
				if (!empty(mysqli_error($conn_sl))){$s.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');}
			}
		}
	}
	// controllo nella tabella delle modifiche (ab_can_l) se posso trovare altri codici fiscali non popolati
	$sql="select distinct LDAP_UID, COD_FISC, KA, KR from c##sss_import.ab_can_l where LDAP_UID is not null and COD_FISC is not null";	
	$a=load_db($conn_new,$sql,'o');
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		for ($i=0; $i < count($a[2]['LDAP_UID']); $i++) {
			if ($a[2]['LDAP_UID'][$i] != ''){
				$sql="update storico_ldap.richieste_account set ra_cf = if (ra_cf is null, '".trim($a[2]['COD_FISC'][$i])."', ra_cf)";
				if (!empty($a[2]['KR'][$i])){$sql.=", ra_ruolo = if (ra_ruolo is null, '".$a[2]['KR'][$i]."', ra_ruolo)";}
				if (!empty($a[2]['KA'][$i])){$sql.=", ra_afferenza = if (ra_afferenza is null, '".$a[2]['KA'][$i]."', ra_afferenza)";}
				$sql.=" where ra_uid='".$a[2]['LDAP_UID'][$i]."'";
				$b=mysqli_query($conn_sl,$sql);
				if (!empty(mysqli_error($conn_sl))){$s.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');}
			}
		}
	}
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
	foreach ($an as $k => $v){
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
		if (!empty($v['KI'])){
			if (!empty($sql_set)){$sql_set.=", ";}
			$sql_set.=" ra_inquadramento = if (ra_inquadramento is null, '".$v['KI']."', ra_inquadramento)";
		}
		$sql.=$sql_set." where ra_note='".$k."'";
		if (!empty($sql_set)){
			$b=mysqli_query($conn_sl,$sql);
			if (!empty(mysqli_error($conn_sl))){$s.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');}
		}
	}
*/	
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
function get_n_acc(){							// form inserimento nuovo account
	global $conn_new, $conn_sl, $devel;
	$s='';
	$nome='account';
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}	// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}	// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}	// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}	// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict

	$tit='<strong>Nuovo Account</strong>';
	// if ($_REQUEST['snac']=='PF'){$tit='<strong>Nuovo Account - Persona fisica</strong>';}
	// if ($_REQUEST['snac']=='GE'){$tit='<strong>Nuovo Account - Generico</strong>';}
	// if ($_REQUEST['snac']=='GU'){$tit='<strong>Nuovo Account - Guest</strong>';}
	$s.=get_alert($tit,'info text-center');
	$s.='<div class="row">';
		$s.='<div class="col-sm-6 alert alert-dark">';
			$s.='<button id="modal-ok" class="btn btn-success" act="get_ra" dom="mm" conferma="y" tab="na" type="ura">Salva bozza nuovo account</button>';
			$s.=get_ra_na(); // form dati account
		$s.='</div>';
		$s.='<div class="col-sm-6">';
			$n='uid_mancanti';
		
			$s.='<div class="row bg-light">';
				$s.='<div class="col-sm-4">';
					$s.='<button class="btn btn-info btn-sm btn-block" act="toggleDTX" tabledom="'.$n.'">Toggle DataTables</button>';
				$s.='</div>';
				$s.='<div class="col-sm-4 text-center"><strong>Account senza LDAP</strong></div>';
				$s.='<div id="alert-'.$n.'" class="col-sm-4"></div>';
			$s.='</div>';
		
			$nc=['CN','CF','MAIL','D'];
			// 1 select = tutti con ldap_uid = null da ugov e alcuni da esse3
			// 2 select = pre immatricolazioni da esse3
			// 3 select = ugov futuri
			$sql="
				SELECT * FROM (
						SELECT
							to_char(dt_rap_fin, 'dd/mm/yyyy') df,
							kr,
							ka,
							ki,
							C##SSS_IMPORT.F_SISTEMA_STRINGHE (nome) nome,
							C##SSS_IMPORT.F_SISTEMA_STRINGHE (cognome) cognome,
							C##SSS_IMPORT.F_SISTEMA_STRINGHE (cognome || ' ' || nome) CN,
							cod_fisc CF,
							mail_esterna MAIL,
							dr || ' ' || di D
						FROM c##sss_import.ab_can
						WHERE
							LDAP_UID IS NULL
							AND (
								kr IN ('COCU5', 'COCU6', 'CO1L', 'CO2L', 'D2', 'D226', 'LM')
								or 
								gest in ('UGOV','AFF')
							)
					UNION
						SELECT
							NULL df,
							kr,
							NULL ka,
							ki,
							C##SSS_IMPORT.F_SISTEMA_STRINGHE (nome) nome,
							C##SSS_IMPORT.F_SISTEMA_STRINGHE (cognome) cognome,
							C##SSS_IMPORT.F_SISTEMA_STRINGHE (cognome || ' ' || nome) CN,
							cod_fis CF,
							email MAIL,
							'pre.imm. - ' || des D
						FROM c##sss_import.esse3_pre_immatr
						WHERE
							cod_fis NOT IN (SELECT DISTINCT cod_fisc FROM c##sss_import.ab_can WHERE ldap_uid IS NOT NULL)
							AND kr IN ('COCU5', 'COCU6', 'CO1L', 'CO2L', 'D2', 'D226', 'LM')
					UNION
						SELECT
							to_char(dt_rap_fin, 'dd/mm/yyyy') df,
							ruolo kr,
							aff_org ka,
							inquadr ki,
							C##SSS_IMPORT.F_SISTEMA_STRINGHE (nome) nome,
							C##SSS_IMPORT.F_SISTEMA_STRINGHE (cognome) cognome,
							C##SSS_IMPORT.F_SISTEMA_STRINGHE (cognome || ' ' || nome) CN,
							cod_fis CF,
							NULL MAIL,
							ds_ruolo || ' ' || ds_inquadr D
						FROM C##SSS_TABLES.V_IE_RU_SGE sge, C##SSS_IMPORT.AB_ATTIVITA_SSS att
						WHERE
							sge.ATTIVITA = att.attivita(+)
							AND TO_CHAR (sge.DT_INI,'yyyy') <= TO_CHAR (SYSDATE,'yyyy') + 1
							AND (TRUNC (sge.DT_INI) > TRUNC (SYSDATE)
							AND TRUNC (sge.dt_rap_fin) > TRUNC (SYSDATE))
							AND att.F_ATTIVO = 1
							AND cod_fis NOT IN (SELECT DISTINCT cf FROM	c##sss_import.anagrafiche_da_ldap)
					UNION
						SELECT 
							to_char(dt_rap_fin, 'dd/mm/yyyy') df,
							kr,
							ka,
							ki,
							C##SSS_IMPORT.F_SISTEMA_STRINGHE (nome) nome,
							C##SSS_IMPORT.F_SISTEMA_STRINGHE (cognome) cognome,
							C##SSS_IMPORT.F_SISTEMA_STRINGHE (cognome || ' ' || nome) CN,
							cod_fisc CF,
							MAIL,
							dr || ' ' || di D
						FROM c##sss_import.ab_cfn 
						WHERE 
							trunc(DT_RAP_INI) >= trunc(sysdate) 
							AND ldap_uid IS NULL 
							AND cod_fisc NOT IN (SELECT DISTINCT cod_fisc FROM c##sss_import.ab_can)					
				)
				ORDER BY CN
			";
			// LM ??
// carriere future SGE (AB_CFN) (da RAC)
// $sql = "SELECT * FROM c##sss_import.ab_carriere_future WHERE GEST='UGOV' AND (".$sw.") ORDER BY COGNOME, NOME";
			
			// $s.=get_alert($sql);
			$a=load_db($conn_new,$sql,'o');
			if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
				$s.='<div class="overflow-auto" style="max-height: 500px;">';
					$s.='<table class="table table-striped table-sm" id="'.$n.'"><thead><tr>';
					$s.='<th></th>';	// azioni
					// $nc=array_keys($a[2]);
					foreach($nc as $f){
						if (in_array($f,$nc)){
							$s.='<th>'.$f.'</th>';
						}
					}
					$s.='</tr></thead><tbody>';
					for($i=0; $i < count($a[2]['CF']); $i++){
						$s.='<tr id="nouid_'.$a[2]['CF'][$i].'">';
							$stl='warning';
							$tit='riporta questi dati di pre immatricolazione nel form di compilazione';
							if (substr($a[2]['D'][$i],0,8) != 'pre.imm.'){
								$stl='info'; 
								$tit='riporta questi dati mancanti nel form di compilazione';
							}
							$s.='<td><button class="btn btn-'.$stl.'" act="get_uid_m" title="'.$tit.'" ka="'.$a[2]['KA'][$i].'" kr="'.$a[2]['KR'][$i].'" ki="'.$a[2]['KI'][$i].'" cf="'.$a[2]['CF'][$i].'" nome="'.$a[2]['NOME'][$i].'" cognome="'.$a[2]['COGNOME'][$i].'" mail="'.$a[2]['MAIL'][$i].'" df="'.$a[2]['DF'][$i].'"><i class="fas fa-angle-double-left"></i></button></td>';
							foreach($nc as $f){
								if (in_array($f,$nc)){
									$s.='<td>'.$a[2][$f][$i].'</td>';
								}
							}
						$s.='</tr>';	
					}
					$s.='</tbosy></table>';						
				}
			$s.='</div>';
		$s.='</div>';
	$s.='</div>';
	$s.='<script type="text/javascript" id="js_temp">
		if (!$.fn.DataTable.isDataTable("#'.$n.'")) {
			dtt("'.$n.'");
		}
		$("#js_temp").remove();
	</script>';
	return $s;
}
function set_ra_log($k,$d,$s){		// scrive sul nuovo log degli account
	global $conn_new, $conn_sl, $devel;
	$nome='account';
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}	// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}	// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}	// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}	// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict

	$e=''; 
	$sql="INSERT INTO storico_ldap.richieste_account_log (ral_ra_k, ral_d, ral_stato, ral_usr_ins, ral_ip) VALUES(".$k.", '".implode("''",explode("'",$d))."', ".(empty($s)?'null':"'".$s."'").", '".$_SESSION['IAM']['uid_login']."', '".getIpAddress()."')";
	$a=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');}
	return $e;
}
function get_ra(){								// richiesta account
	global $conn_new, $conn_sl, $devel, $sviluppo_albo;
	
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra - inizio");}
	$nome='account';
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}	// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}	// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}	// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}	// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict

	if (empty($_REQUEST['mail'])){$_REQUEST['mail']='';}
	if (empty($_REQUEST['nome'])){$_REQUEST['nome']='';}
	if (empty($_REQUEST['cognome'])){$_REQUEST['cognome']='';}
	if (empty($_REQUEST['stato'])){$_REQUEST['stato']='';}
	$j=array();
	if (!empty($_REQUEST['k'])){
		$sql="select * from storico_ldap.richieste_account where ra_k=".$_REQUEST['k'];
		$a=mysqli_query($conn_sl,$sql);
		if (empty(mysqli_error($conn_sl))){
			// $s.='<br />'.mysqli_error($conn_sl);
			if (mysqli_num_rows($a) > 0) {	// dati trovati
				foreach($a as $row){
					$j[]=$row;
				}
			}
		}
		if (!empty($j[0]['ra_mail_notifica']) and empty($_REQUEST['mail'])){$_REQUEST['mail']=$j[0]['ra_mail_notifica'];}
		if (!empty($j[0]['ra_nome']) and empty($_REQUEST['nome'])){$_REQUEST['nome']=$j[0]['ra_nome'];}
		if (!empty($j[0]['ra_cognome']) and empty($_REQUEST['cognome'])){$_REQUEST['cognome']=$j[0]['ra_cognome'];}
		if (!empty($j[0]['ra_stato']) and empty($_REQUEST['stato'])){$_REQUEST['stato']=$j[0]['ra_stato'];}
	}	
	$mail=$_REQUEST['mail'];
	$nome=$_REQUEST['nome'];
	$cognome=$_REQUEST['cognome'];
	$stato=$_REQUEST['stato'];

	// bozza, richiesta, da_elaborare, completata, rifiutata
	$tit='';	
	$msg='';	
	if ($devel and $su and (!empty($sql))) {$msg.=get_alert($sql);}
	if ($devel and $su) {$msg.=get_alert(str_replace('","','", "',json_encode($j)));}
	$stl='';	
	$btn='';

	$sql='';
	$e='';

	if (!empty($_REQUEST['conferma'])){
		// conferma azione
		switch ($_REQUEST['type']){
			case "era":	//	Elabora richiesta account (prepara) - stato => aspetta_ad
				/*
					contenuto della REQUEST:
					{"tipo_account":"PF", "nome":"PIETRO", "cognome":"BONACCORSI", "email_ex":"bonaccorsip@gmail.com", "cf":"BNCPTR96C23G702D", "ka":"330323", "kr":"CO2L", "di":"21\/03\/2022", "df":"02\/04\/2022", "docint":"DSMNTN62H30F839N", "func":"get_ra", "obj_val":"", "class":"btn btn-success btn-block", "act":"get_ra", "dom":"mm", "conferma":"y", "type":"era", "k":"6162", "tab":"na", "getf":"uid,password,tipo,alias", "uid":"pi.bonaccorsi", "password":"VSI798z2", "tipo":"PF", "alias":"pietro.bonaccorsi", "mail":"bonaccorsip@gmail.com"}
				*/
				// ultimi controlli sui dati confermati
				if (strlen($_REQUEST['password']) < 8){$e.='<br />'.'Password con meno di 8 caratteri';}
				if (strlen($_REQUEST['uid'])  < 3){$e.='<br />'.'uid con meno di 3 caratteri';}
				if (stripos($_REQUEST['uid'],'.') === false){$e.='<br />'.'uid senza il punto';}
				if (strlen($_REQUEST['alias']) > 40){$e.='<br />'.'alias di oltre 40 caratteri';} // + 16 crt '@santannapisa.it'
				
				if (empty($e)){
					if (!empty($_REQUEST['k'])){
						$sql="update storico_ldap.richieste_account set ";
						$sql.=" ra_stato='aspetta_ad'";													// ra_stato
						$sql.=" ,ra_uid='".$_REQUEST['uid']."'";								// ra_uid
						$sql.=" ,ra_prima_password='".$_REQUEST['password']."'";				// ra_prima_password
						$sql.=" ,ra_aliases='".$_REQUEST['alias']."'";					// ra_aliases
						$sql.=" ,ra_ad_princ_mail='".$_REQUEST['alias']."@santannapisa.it'";	// ra_ad_princ_mail
						$sql.=" ,ra_usr_mod='".$_SESSION['IAM']['uid_login']."'";				// ra_usr_mod
						$sql.=" where ra_k=".$_REQUEST['k'];
					}
// if ($sviluppo_albo){write_log('_tmp.log',"ERA - sql: ".$sql);}

					try {
						$a=mysqli_query($conn_sl,$sql);						
						if (!empty(mysqli_error($conn_sl))){$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');}
					} catch (Exception $ee) {
						$e.=get_alert(($su?$sql.'<br />':'').'Exception: '.$ee->getMessage(),'danger');
					}

					// $a=mysqli_query($conn_sl,$sql);
					// if (!empty(mysqli_error($conn_sl))){$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');}
					
					$e.=set_ra_log($_REQUEST['k'],"era - Elabora richiesta account - tipo='".$_REQUEST['tipo']."', uid='".$_REQUEST['uid']."', prima_password='".$_REQUEST['password']."', aliases='".$_REQUEST['alias']."@santannapisa.it'".(empty($e)?'':' - ERROR'),$stato); // scrivo il log
				}
				
				$tit.='Elabora richiesta account';
				// $msg.=get_alert(str_replace('","','", "',json_encode($_REQUEST))); // @@@@@@@@
				if (empty($e)){
					$msg.='L\'elaborazione della richiesta di account di <strong>'.$nome.' '.$cognome.'</strong> &egrave; stata correttamente eseguita';
					$stl.='bg-warning text-center text-white';
				} else {
					$msg.='L\'elaborazione della richiesta di account di <strong>'.$nome.' '.$cognome.'</strong> ha dato errore';
					$msg.=$e;
					$stl.='bg-danger text-center text-white';
				}
				break;
			case "ura":	//	Modifica richiesta account
				if (empty($_REQUEST['di'])){$_REQUEST['di']=date("d/m/Y");}

				// calcola data plausibile di fine
				if (empty($_REQUEST['df'])){
					$_REQUEST['df']="02/02/2222";	 // @@@@ default
					if (!empty($_REQUEST['kr'])){
						$lr=$_SESSION['IAM']['lista_ruoli'];
						$n=array_search($_REQUEST['kr'],$lr['KR']);
						if ($n !== false){
							if (!empty($lr['ANNI_WARNING'][$n])){
								$aw=$lr['ANNI_WARNING'][$n];
								$_REQUEST['df']=date('d/m/Y', strtotime('+'.$aw.' years'));
							}
						}
					}
				}
				
				if ($_REQUEST['tipo_account']=='GE'){
					if (empty($_REQUEST['cognome'])){$e.=get_alert('Manca la denominazione','danger');}
				}
				if ($_REQUEST['tipo_account']=='PF' or $_REQUEST['tipo_account']=='GU'){
					if (empty($_REQUEST['cognome'])){
						$e.=get_alert('Manca il cognome','danger');
					} else {
						if (strlen($_REQUEST['cognome']) > 17){	// @@@@@@@@@@@@@@@@@@@@@
							$e.=get_alert('Cognome troppo lungo (massimo 17 crt)','danger');
						}
						if (strlen($_REQUEST['cognome']) < 2){
							$e.=get_alert('Cognome troppo corto (minimo 2 crt)','danger');
						}
					}
					if (empty($_REQUEST['nome'])){
						$e.=get_alert('Manca il nome','danger');
					} else {
						if (strlen($_REQUEST['nome']) < 2){
							$e.=get_alert('Nome troppo corto (minimo 2 crt)','danger');
						}
					}
					if (empty($_REQUEST['cf'])){
						$e.=get_alert('Manca il codice fiscale','danger');
					} else {
						$e=ControllaCF(trim($_REQUEST['cf'])); // controllo validità del CF (formale)
						if (!empty($e)){
							$e=get_alert('<strong>'.trim($_REQUEST['cf']).'</strong><br />'.$e,'danger');
						}
					}
					if (empty($_REQUEST['kr'])){$e.=get_alert('Manca la struttura','danger');}
					if (empty($_REQUEST['ka'])){$e.=get_alert('Manca la categoria','danger');}
				}
				if ($_REQUEST['tipo_account']=='GE' or $_REQUEST['tipo_account']=='GU'){
					if (empty($_REQUEST['docint'])){$e.=get_alert('Manca il referente interno','danger');}
				}
				
				$tit.='Salva richiesta account';
				if (empty($e)){
					$ncr='';	// nome e cognome referente
					if ($su or $ict){
						if (isset($_REQUEST['ra_nc_referente'])){
							$ncr=ucwords(strtolower(trim(substr(implode("''",explode("'",htmlentities($_REQUEST['ra_nc_referente']))),0,64))));
						}
					} else {
						if (!empty($_SESSION['IAM']['ab_can'])){
							// cerco nome e cognome del referente
							$i=array_search($_REQUEST['docint'],$_SESSION['IAM']['ab_can']['COD_FISC']);
							if ($i !== false){
								$ncr=ucwords(strtolower(trim(substr(implode("''",explode("'",htmlentities($_SESSION['IAM']['ab_can']['COGNOME'][$i].' '.$_SESSION['IAM']['ab_can']['NOME'][$i]))),0,64))));
								// $ncr=$_SESSION['IAM']['ab_can']['NOME'][$i].' '.$_SESSION['IAM']['ab_can']['COGNOME'][$i];
							}
						}
					}
					
					$da='';	// descrizione afferenza (necessaria per la creazione in AD)
					$i=array_search($_REQUEST['ka'],$_SESSION['IAM']['lista_afferenze']['KA']);
					if ($i !== false){$da=substr(implode("''",explode("'",htmlentities($_SESSION['IAM']['lista_afferenze']['DA'][$i]))),0,64);}
					if (($su or $ict) and empty($da)){
						if (isset($_REQUEST['ra_d_afferenza'])){
							$da=substr(implode("''",explode("'",htmlentities($_REQUEST['ra_d_afferenza']))),0,64);
						}
					}
					
					$w='';	// warning
					if (empty($_REQUEST['k'])){
						// inserimento nuova richiesta (in bozza)
						// prima di inserire controllare se esiste in richieste_account
						// 		stesso CF = ERRORE
						//		stesso Cognome e nome = WARNING
						//		edit distance Cognome e nome < 3 = INFO

						$sql="select * from storico_ldap.richieste_account where ra_cf='".trim($_REQUEST['cf'])."'";
						$sql.=" or (lower(ra_cognome)='".strtolower($cognome)."' and lower(ra_nome)='".strtolower($nome)."')";
						$x=array();
						$a=mysqli_query($conn_sl,$sql);
						if (empty(mysqli_error($conn_sl))){
							if (mysqli_num_rows($a) > 0) {	// dati trovati
								foreach($a as $row){$x[]=$row;}
							}
						}
						if (count($x) > 0) {	// dati trovati
							$a=array();
							$a['tit']='Crea bozza nuovo account';
							for($xn=0; $xn<count($x); $xn++){
								if ($x[$xn]['ra_cf'] == trim($_REQUEST['cf'])){
									$e.=get_alert('Codice fiscale <strong>'.trim($_REQUEST['cf']).'</strong> esistente per <strong>'.$x[$xn]['ra_nome'].' '.$x[$xn]['ra_cognome'].'</strong> - uid: '.$x[$xn]['ra_uid'],'danger');
								} else {
									// dovrebbe dare solo un warning @@@@@@@@@@@@
									$w.=get_alert('Nome e cognome <strong>'.$x[$xn]['ra_nome'].' '.$x[$xn]['ra_cognome'].'</strong> esistente per uid: '.$x[$xn]['ra_uid'].' - c.f.: '.$x[$xn]['ra_cf'],'warning');
								}
							}
						}
						// controllo simili
						for($x=0; $x<count($_SESSION['IAM']['ab_can']['COD_FISC']); $x++){
							$can_nome=$_SESSION['IAM']['ab_can']['NOME'][$x];
							$can_cognome=$_SESSION['IAM']['ab_can']['COGNOME'][$x];
							$can_uid=$_SESSION['IAM']['ab_can']['LDAP_UID'][$x];
							$can_cf=$_SESSION['IAM']['ab_can']['COD_FISC'][$x];
							$current_nc=strtolower($_REQUEST['nome'].$_REQUEST['cognome']);
							if (levenshtein(strtolower($can_nome.$can_cognome),$current_nc) < 3 and levenshtein(strtolower($can_nome.$can_cognome),$current_nc) > 0){
								$w.=get_alert('Nome e cognome simile: <strong>'.$can_nome.' '.$can_cognome.'</strong> esistente per uid: '.$can_uid.' - c.f.: '.$can_cf,'info');
							}
						}
						
						if ($e != ''){
							$a=array();
							$a['tit']='Crea bozza nuovo account';
							$a['msg']=$e;
							$a['stl']='bg-danger text-center text-white';
							$a['btn']='';
							return json_encode($a);
						}
						
						$sql="insert into storico_ldap.richieste_account (ra_tipo, ra_nome, ra_cognome, ra_cf, ra_mail_notifica, ra_ruolo, ra_afferenza, ra_d_afferenza, ra_inizio, ra_fine, ra_cf_referente, ra_nc_referente, ra_stato, ra_note, ra_usr_ins, ra_cancellabile) values (";
						$sql.="'".$_REQUEST['tipo_account']."'";					// ra_tipo (PF, GE, GU)
						$sql.=",'".addslashes(ucwords(strtolower(trim(ab_str_utf8_ascii($_REQUEST['nome'])))))."'";		// ra_nome
						$sql.=",'".addslashes(ucwords(strtolower(trim(ab_str_utf8_ascii($_REQUEST['cognome'])))))."'";	// ra_cognome
						$sql.=",'".addslashes(trim(crt_filter(strtoupper($_REQUEST['cf']),'§ma§nu')))."'";	// ra_cf
						$sql.=",'".addslashes(strtolower($_REQUEST['email_ex']))."'";						// ra_mail_notifica
						$sql.=",'".$_REQUEST['kr']."'";								// ra_ruolo
						$sql.=",'".$_REQUEST['ka']."'";								// ra_afferenza
						$da='';	// descrizione afferenza (necessaria per la creazione in AD)
						$i=array_search($_REQUEST['ka'],$_SESSION['IAM']['lista_afferenze']['KA']);
						if ($i !== false){$da=substr(implode("''",explode("'",htmlentities(ab_str_utf8_ascii($_SESSION['IAM']['lista_afferenze']['DA'][$i])))),0,64);}
						$sql.=",'".$da."'";											// ra_d_afferenza
						$sql.=",'".convert_date($_REQUEST['di'])."'";				// ra_inizio
						$sql.=",'".convert_date($_REQUEST['df'])."'";				// ra_fine
						$sql.=",'".trim(crt_filter(strtoupper($_REQUEST['docint']),'§ma§nu'))."'";			// ra_cf_referente
						$sql.=",'".addslashes($ncr)."'"; 										// ra_nc_referente
						$sql.=",'bozza'"; 											// ra_stato
						$sql.=",'".addslashes($_REQUEST['note'])."'"; 							// ra_note
						$sql.=",'".$_SESSION['IAM']['uid_login']."'";		// ra_usr_ins
						$sql.=",1";													// ra_cancellabile
						$sql.=")";
					} else {
						// modifica
						$sql="update storico_ldap.richieste_account set";
						$sql.= " ra_tipo='".$_REQUEST['tipo_account']."'";			// ra_tipo (PF, GE, GU)
						$sql.=", ra_nome='".addslashes(ucwords(strtolower(trim(ab_str_utf8_ascii($_REQUEST['nome'])))))."'";
						$sql.=", ra_cognome='".addslashes(ucwords(strtolower(trim(ab_str_utf8_ascii($_REQUEST['cognome'])))))."'";
						$sql.=", ra_cf='".addslashes(strtoupper(trim($_REQUEST['cf'])))."'";
						$sql.=", ra_mail_notifica='".addslashes($_REQUEST['email_ex'])."'";
						$sql.=", ra_ruolo='".$_REQUEST['kr']."'";
						$sql.=", ra_afferenza='".$_REQUEST['ka']."'";
						$sql.=", ra_d_afferenza='".addslashes(ab_str_utf8_ascii($da))."'";
						$sql.=", ra_inizio='".convert_date($_REQUEST['di'])."'";
						$sql.=", ra_fine='".convert_date($_REQUEST['df'])."'";
						$sql.=", ra_cf_referente='".strtoupper($_REQUEST['docint'])."'";
						$sql.=", ra_nc_referente='".addslashes($ncr)."'";
						$sql.=", ra_note='".addslashes($_REQUEST['note'])."'";
						$sql.=", ra_usr_mod='".$_SESSION['IAM']['uid_login']."'";
						if (($su or $ict) and !empty($_REQUEST['ra_stato'])){
							$sql.=", ra_stato = '".implode("''",explode("'",$_REQUEST['ra_stato']))."'";
						} 
						if ($su){
							// $asu=array('ra_mail','ra_notificata','ra_dt_notifica','ra_d_afferenza','ra_inquadramento','ra_nuovo_inizio','ra_nc_referente','ra_stato','ra_motivo','ra_uid','ra_aliases','ra_redirect','ra_prima_password','ra_modifica_password','ra_cancellabile','ra_nfcm','ra_ldap_dn_ini','ra_ad_motivo','ra_ad_princ_mail','ra_ad_dn_ini');
							// $asu=array('ra_mail','ra_notificata','ra_dt_notifica','ra_inquadramento','ra_nuovo_inizio','ra_stato','ra_motivo','ra_uid','ra_aliases','ra_redirect','ra_prima_password','ra_modifica_password','ra_cancellabile','ra_nfcm','ra_ldap_dn_ini','ra_ad_motivo','ra_ad_princ_mail','ra_ad_dn_ini');
							$asu=array('ra_mail','ra_notificata','ra_dt_notifica','ra_inquadramento','ra_nuovo_inizio','ra_motivo','ra_uid','ra_aliases','ra_redirect','ra_prima_password','ra_modifica_password','ra_uid_modifica_password','ra_cancellabile','ra_nfcm','ra_ldap_dn_ini','ra_ad_motivo','ra_ad_princ_mail','ra_ad_dn_ini');
// ra_k, ra_tipo, ra_nome, ra_cognome, ra_mail, ra_cf, ra_mail_notifica, ra_notificata, ra_dt_notifica, ra_ruolo, ra_afferenza, ra_d_afferenza, ra_inquadramento, ra_inizio, ra_nuovo_inizio, ra_fine, ra_cf_referente, ra_nc_referente, ra_stato, ra_motivo, ra_uid, ra_aliases, ra_redirect, ra_prima_password, ra_modifica_password, ra_cancellabile, ra_note, ra_nfcm, ra_ldap_dn_ini, ra_ad_motivo, ra_ad_princ_mail, ra_ad_dn_ini, ra_usr_ins, ra_dt_ins, ra_ip_ins, ra_usr_mod, ra_dt_mod, ra_ip_mod							
							
							foreach ($asu as $asui){
								$asuitf=true;
								if (in_array($asui,array('ra_notificata','ra_cancellabile'))){
									if (trim($_REQUEST[$asui]) != ''){
										$sql.=", ".$asui."=".$_REQUEST[$asui]; 
									}
									$asuitf=false;
								}
								if (in_array($asui,array('ra_dt_notifica','ra_nuovo_inizio'))){
									if (trim($_REQUEST[$asui]) != ''){
										$sql.=", ".$asui."='".convert_date($_REQUEST[$asui])."'";
									}
									$asuitf=false;
								}
								if ($asuitf) {
									$sql.=", ".$asui."='".implode("''",explode("'",$_REQUEST[$asui]))."'";	// accetta anche stringa vuota
								} 
							}
						}
						$sql.=" where ra_k=".$_REQUEST['k'];
					}
// if ($sviluppo_albo){write_log('_tmp.log',"URA - sql: ".$sql);}
					$a=mysqli_query($conn_sl,$sql);
					if (!empty(mysqli_error($conn_sl))){
						$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');
					}

					if ($e != ''){
						$a=array();
						$a['tit']='Crea bozza nuovo account';
						$a['msg']=$e;
						$a['stl']='bg-danger text-center text-white';
						$a['btn']='';
						return json_encode($a);
					}

					$ss = "ura - ".(empty($_REQUEST['k'])?'insert':'update')." - tipo = ".$_REQUEST['tipo_account'].", nc = ".ucwords(strtolower(trim(ab_str_utf8_ascii($_REQUEST['nome']))))." ".ucwords(strtolower(trim(ab_str_utf8_ascii($_REQUEST['cognome'])))).", cf = ".trim($_REQUEST['cf']).", mail notifica = ".$_REQUEST['email_ex'].", inizio = ".$_REQUEST['di'].", fine = ".$_REQUEST['df'].", stato = bozza";
					if (!empty($ncr)){$ss.=", referente = ".ucwords(strtolower(trim(ab_str_utf8_ascii($ncr))));}
					$e.=set_ra_log((empty($_REQUEST['k'])?'0':$_REQUEST['k']),$ss,$stato); // scrivo il log

					if ($su and !empty($e)) {$msg.=get_alert($sql);}
					if (empty($e)){
						if ($su) {$msg.=get_alert($sql);}
						$msg.='La richiesta di account di <strong>'.$nome.' '.$cognome.'</strong> &egrave; stata correttamente salvata'.$w;	// aggiungo l'eventuale warning
						$stl.='bg-primary text-center text-white';
					} else {
						$msg.='Il tentativo di salvare in bozza la richiesta di account di <strong>'.$nome.' '.$cognome.'</strong> ha dato errore';
						$msg.=$e;
						$stl.='bg-danger text-center text-white';
					}
				}
				break;
			case "bra":	//	Conferma riporto richiesta account (stato => bozza)
				// valutare se tornare sempre a bozza o tornare indietro di un passo rispetto stato attuale
				if (!empty($_REQUEST['k'])){
					$sql="update storico_ldap.richieste_account set ";
					$sql.=" ra_stato='bozza'";														// ra_stato
					$sql.=" ,ra_usr_mod='".$_SESSION['IAM']['uid_login']."'";				// ra_usr_mod
					$sql.=" where ra_k=".$_REQUEST['k'];
// if ($sviluppo_albo){write_log('_tmp.log',"BRA - sql: ".$sql);}
					$a=mysqli_query($conn_sl,$sql);
					if (!empty(mysqli_error($conn_sl))){$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');}

					$e.=set_ra_log($_REQUEST['k'],"bra - Conferma riporto richiesta account (torna a bozza)",$stato); // scrivo il log

				}
				$tit.='Ripristina in bozza la richiesta account';
				if (empty($e)){
					$msg.='La richiesta di account di <strong>'.$nome.' '.$cognome.'</strong> &egrave; stata correttamente riportata in stato di bozza';
					$stl.='bg-success text-center text-white';
				} else {
					$msg.='Il tentativo di riportare in bozza la richiesta di account di <strong>'.$nome.' '.$cognome.'</strong> ha dato errore';
					$msg.=$e;
					$stl.='bg-danger text-center text-white';
				}
				break;
			case "cra":	//	Conferma richiesta account (stato => richiesta)
				// l'utente che inserisce ha validato dal suo lato la richiesta e ha avviato l'iter
				if (!empty($_REQUEST['k'])){
					$sql="update storico_ldap.richieste_account set ";
					$sql.=" ra_stato='richiesta'";														// ra_stato
					$sql.=" ,ra_usr_mod='".$_SESSION['IAM']['uid_login']."'";				// ra_usr_mod
					$sql.=" where ra_k=".$_REQUEST['k'];
// if ($sviluppo_albo){write_log('_tmp.log',"CRA - sql: ".$sql);}
					$a=mysqli_query($conn_sl,$sql);
					if (!empty(mysqli_error($conn_sl))){$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');}
					$e.=set_ra_log($_REQUEST['k'],"cra - Conferma richiesta account",$stato); // scrivo il log
				}
				$tit.='Conferma richiesta account';
				if (empty($e)){
					$msg.='La richiesta di account di <strong>'.$nome.' '.$cognome.'</strong> &egrave; stata correttamente confermata';
					$stl.='bg-success text-center text-white';
				} else {
					$msg.='Il tentativo di confermare la richiesta di account di <strong>'.$nome.' '.$cognome.'</strong> ha dato errore';
					$msg.=$e;
					$stl.='bg-danger text-center text-white';
				}
				break;
			case "dra":	//	Cancella richiesta account (non deve essere completato o storicizzato)
				if (!empty($_REQUEST['k'])){
					$sql="select * from storico_ldap.richieste_account where ra_k=".$_REQUEST['k'];
					$x=array();
					$a=mysqli_query($conn_sl,$sql);
					if (empty(mysqli_error($conn_sl))){
						if (mysqli_num_rows($a) > 0) {	// dati trovati
							foreach($a as $row){$x[]=$row;}
						}
					}
					if (count($x) > 0) {	// dati trovati
						if ($x[0]['ra_stato'] == 'completato' or $x[0]['ra_stato'] == 'storicizzato'){
							$e=get_alert('Impossibile cancellare - stato: <strong>'.$x[0]['ra_stato'].'</strong>','danger');
						}
					} else {
						$e=get_alert('Impossibile cancellare - <strong>record non trovato</strong>','danger');
					}
					if (empty($e)){

						// $sql="delete from storico_ldap.richieste_account_log where ral_ra_k=".$_REQUEST['k'];
						// $a=mysqli_query($conn_sl,$sql);
						// if (!empty(mysqli_error($conn_sl))){$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');}					

						$sql="delete from storico_ldap.richieste_account_alias where raa_ra_k=".$_REQUEST['k'];
						$a=mysqli_query($conn_sl,$sql);
						if (!empty(mysqli_error($conn_sl))){$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');}

						$sql="delete from storico_ldap.richieste_account where ra_k=".$_REQUEST['k'];
						$a=mysqli_query($conn_sl,$sql);
						if (!empty(mysqli_error($conn_sl))){$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');}
					
						$e.=set_ra_log($_REQUEST['k'],"dra - Cancella richiesta account",$stato); // scrivo il log
					}
				}
				$tit.='Cancella richiesta account';
				if (empty($e)){
					$msg.='La richiesta di account  di <strong>'.$nome.' '.$cognome.'</strong> &egrave; stata correttamente cancellata';
					$stl.='bg-danger text-center text-white';
				} else {
					$msg.='Il tentativo di cancellare la richiesta di account di <strong>'.$nome.' '.$cognome.'</strong> ha dato errore';
					$msg.=$e;
					$stl.='bg-danger text-center text-white';
				}
				break;
			case "yra":	//	Approva richiesta account (stato => validata_ict)
				// @@@@@ questo passaggio potrebbe essere saltato
				// step necessario per la verifica da parte del responsabile ICT ???
				if (!empty($_REQUEST['k'])){
					$sql="update storico_ldap.richieste_account set ";
					$sql.=" ra_stato='validata_ict'";												// ra_stato
					$sql.=" ,ra_usr_mod='".$_SESSION['IAM']['uid_login']."'";				// ra_usr_mod
					$sql.=" where ra_k=".$_REQUEST['k'];
// if ($sviluppo_albo){write_log('_tmp.log',"YRA - sql: ".$sql);}
					$a=mysqli_query($conn_sl,$sql);
					if (!empty(mysqli_error($conn_sl))){$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');}
					$e.=set_ra_log($_REQUEST['k'],"yra - Valida richiesta account",$stato); // scrivo il log
				}
				$tit.='Valida richiesta account';
				if (empty($e)){
					$msg.='La richiesta di account  di <strong>'.$nome.' '.$cognome.'</strong> &egrave; stata correttamente validata';
					$stl.='bg-success text-center text-white';
				} else {
					$msg.='Il tentativo di validare la richiesta di account di <strong>'.$nome.' '.$cognome.'</strong> ha dato errore';
					$msg.=$e;
					$stl.='bg-danger text-center text-white';
				}
				break;
			case "pra":	//	Stampa richiesta account (non chiede conferma)
				// switch ($j[0]['ra_tipo']){
					// case "PF":	//	persona fisica
						// $msg=get_pdf_account($nome.' '.$cognome,$j[0]['ra_uid'],$j[0]['ra_prima_password'],$j[0]['ra_aliases']);
						// $e.=set_ra_log($_REQUEST['k'],"pra - stampa",$stato); // scrivo il log
						// break;
					// case "GE":	//	generico
						// $msg=get_pdf_account($nome.' '.$cognome,$j[0]['ra_uid'],$j[0]['ra_prima_password'],$j[0]['ra_aliases']);
						// $e.=set_ra_log($_REQUEST['k'],"pra - stampa",$stato); // scrivo il log
						// break;
					// case "GU":	//	guest
						// $msg=get_pdf_account_guest($nome,$cognome,$j[0]['ra_uid'],$j[0]['ra_prima_password'],$j[0]['ra_fine'],$j[0]['ra_note'],$j[0]['ra_aliases'],$j[0]['ra_cf']);
						// $e.=set_ra_log($_REQUEST['k'],"pra - stampa",$stato); // scrivo il log
						// break;
				// }					
				break;
			case "mra":	//	Invia per email la richiesta account
				$sql="select * from storico_ldap.richieste_account where ra_k=".$_REQUEST['k'];
				$x=array();
				$a=mysqli_query($conn_sl,$sql);
				if (empty(mysqli_error($conn_sl))){
					if (mysqli_num_rows($a) > 0) {	// dati trovati
						foreach($a as $row){$x[]=$row;}
					}
				}
				if (count($x) <= 0) {	// dati non trovati
					$e=get_alert('Impossibile inviare - <strong>record non trovato</strong>','danger');
				} else {
					// controlli
					if (empty($x[0]['ra_mail_notifica'])){
						// il controllo dovrebbe essere già stato fatto in fase di creazione del comando di invio
						$e=get_alert('Impossibile inviare - <strong>mail notifica non specificata</strong>','danger');
					}
					if (empty($x[0]['ra_ad_princ_mail'])){
						$e=get_alert('Impossibile inviare - <strong>mail santannapisa.it non determinata</strong>','danger');
					}
				}

				if (empty($e)){
					// crea pdf e invia come allegato 
					// mail_con_allegato($mittente, $destinatario, $oggetto, $messaggio, $allegato='')

					$oggetto='Credenziali account santannapisa.it';

					$mittente='helpdesk@santannapisa.it';
					if (!empty($_SESSION['IAM']['myDU']['MAIL'][0])){
						$mittente=$_SESSION['IAM']['myDU']['MAIL'][0];
					}
					if ($devel){$mittente='alberto.bongiorni@santannapisa.it';} // @@@@@@@@@@ lasciare fino a che non è in produzione

					$destinatario=$x[0]['ra_mail_notifica'];
					if ($devel){$destinatario='alberto.bongiorni@santannapisa.it';} // @@@@@@@@@@ lasciare fino a che non è in produzione

					$messaggio='Gentile '.ucwords(strtolower($nome.' '.$cognome));
					$messaggio.='<br /><br />con la presente le comunichiamo i dati dell\'account:';
					$messaggio.='<br /><strong>'.$x[0]['ra_ad_princ_mail'].'</strong>'; // mail assegnata
					$messaggio.='<br /><br />cordialmente';
					$nome_user=ucwords(strtolower($_SESSION['IAM']['myDU']['NOME'][0]));
					$cognome_user=ucwords(strtolower($_SESSION['IAM']['myDU']['COGNOME'][0]));
					$messaggio.='<br /><br />'.$nome_user.' '.$cognome_user;
					
					switch ($j[0]['ra_tipo']){
						case "PF":	//	persona fisica
							// $nome_pdf=get_pdf_account($nome.' '.$cognome,$j[0]['ra_uid'],$j[0]['ra_prima_password'],$j[0]['ra_ad_princ_mail']);
							$nome_pdf=get_pdf_account($j[0]);
							break;
						case "GE":	//	generico
							// $nome_pdf=get_pdf_account($nome.' '.$cognome,$j[0]['ra_uid'],$j[0]['ra_prima_password'],$j[0]['ra_ad_princ_mail']);
							break;
						case "GU":	//	guest
							// $nome_pdf=get_pdf_account_guest($nome,$cognome,$j[0]['ra_uid'],$j[0]['ra_prima_password'],$j[0]['ra_fine'],$j[0]['ra_note'],$j[0]['ra_aliases'],$j[0]['ra_cf']);
							break;
					}					
					mail_con_allegato($mittente, $destinatario, $oggetto, $messaggio, $nome_pdf);
					// aggiorno la data di notifica per allertare dell'ultimo invio in caso di reinvio
					if (!empty($_REQUEST['k'])){
						$sql="update storico_ldap.richieste_account set ";
						$sql.=" ra_notificata=1";									// ra_notificata
						$sql.=" ,ra_dt_notifica='".date("Y-m-d")."'";				// ra_dt_notifica
						$sql.=" where ra_k=".$_REQUEST['k'];
// if ($sviluppo_albo){write_log('_tmp.log',"MRA - sql: ".$sql);}
						$a=mysqli_query($conn_sl,$sql);
						if (!empty(mysqli_error($conn_sl))){$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');}
						$e.=set_ra_log($_REQUEST['k'],"mra - notifica a ".$mail,$stato); // scrivo il log
					}
				}
				$tit.='Invia richiesta account';
				if (empty($e)){
					$msg.='I dati dell\'account di <strong>'.$nome.' '.$cognome.'</strong> sono stati correttamente inviati all\'indirizzo<br /><strong>'.$destinatario.'</strong>';
					$stl.='bg-info text-center text-white';
				} else {
					$msg.='Il tentativo di inviare i dati della richiesta di account di <strong>'.$nome.' '.$cognome.'</strong> all\'indirizzo<br /><strong>'.$destinatario.'</strong> ha dato errore';
					// $msg.=$e;
					$stl.='bg-danger text-center text-white';
				}
				break;
			case "sra":	//	Storicizza richiesta account (stato => storicizzata)
				// nascosta alla gestione e visibile solo su richiesta
				if (!empty($_REQUEST['k'])){
					$sql="update storico_ldap.richieste_account set ";
					$sql.=" ra_stato='storicizzata'";										// ra_stato
					$sql.=" ,ra_usr_mod='".$_SESSION['IAM']['uid_login']."'";		// ra_usr_mod
					$sql.=" ,ra_cancellabile=0";											// ra_cancellabile
					$sql.=" where ra_k=".$_REQUEST['k'];
// if ($sviluppo_albo){write_log('_tmp.log',"SRA - sql: ".$sql);}
					$a=mysqli_query($conn_sl,$sql);
					if (!empty(mysqli_error($conn_sl))){$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');}

					$e.=set_ra_log($_REQUEST['k'],"sra - storicizza",$stato); // scrivo il log

				}
				$tit.='Storicizza richiesta account';
				if (empty($e)){
					$msg.='La richiesta di account  di <strong>'.$nome.' '.$cognome.'</strong> &egrave; stata correttamente storicizzata';
					$stl.='bg-success text-center text-white';
				} else {
					$msg.='Il tentativo di storicizzare la richiesta di account di <strong>'.$nome.' '.$cognome.'</strong> ha dato errore';
					$msg.=$e;
					$stl.='bg-danger text-center text-white';
				}
				break;
			case "lra":
//				// log richiesta account (non ci passa perché viene chiamata direttamente la conferma)
//				$tit.='Log account di <strong>'.$nome.' '.$cognome.'</strong>';
//				$msg.='';
//				$stl.='bg-secondary text-center text-white';
				break;
		}
		if (!empty($e)){
			$msg.=$e;
			$tit.=' - ERRORE';
			$stl='bg-danger text-center text-white';
			$btn='';
		}
		
	} else {
		// if (!empty($_REQUEST['conferma'])){
		// richieste di conferma azione
		switch ($_REQUEST['type']){
			case "era":	//	Elabora richiesta account
				$tit.='Elabora richiesta account';
				$msg.='Confermi l\'elaborazione della richiesta di account di <strong>'.$nome.' '.$cognome.'</strong> ?';
				$stl.='bg-warning text-center text-white';
				$btn='<button id="modal-ok" class="btn btn-success" data-dismiss="modal" act="get_e_ra" dom="ga_bottom" type="'.$_REQUEST['type'].'" k="'.$_REQUEST['k'].'">Conferma (prepara)</button><button id="modal-ko" class="btn btn-dark" data-dismiss="modal">Annulla</button>';
				break;
			case "ura":	//	Modifica richiesta account
				$tit.='Modifica bozza richiesta account';
// if ($sviluppo_albo){write_log('_tmp.log',"Modifica bozza richiesta account - inizio");}
				$msg.=get_ra_na(); // form dati account
// if ($sviluppo_albo){write_log('_tmp.log',"Modifica bozza richiesta account - fine");}
				$stl.='bg-primary text-center text-white';
				break;
			case "bra":	//	Conferma regresso richiesta account (da richiesta a bozza)
				$tit.='Conferma regresso richiesta account';
				$msg.='Confermi di riportare la richiesta di account  di <strong>'.$nome.' '.$cognome.'</strong> in stato di bozza ?';
				$stl.='bg-success text-center text-white';
				break;
			case "cra":	//	Conferma richiesta account (da bozza a richiesta)
				// prima di permettere la conferma controllare che abbia i requisiti
				$w='';
				$e='';
				if (!empty($_REQUEST['k'])){
					if (!empty($j)){
						if (empty($j[0]['ra_tipo'])){$e.='<br />'.'manca il tipo account';}
						if (empty($j[0]['ra_nome'])){$e.='<br />'.'manca il nome';}
						if (empty($j[0]['ra_cognome'])){$e.='<br />'.'manca il cognome';}
						if (empty($j[0]['ra_cf'])){$e.='<br />'.'manca il Codice fiscale';}
						if (empty($j[0]['ra_mail_notifica'])){$w.='<br />'.'manca la mail di notifica';}
						if (empty($j[0]['ra_ruolo'])){$e.='<br />'.'manca il ruolo';}
						if (empty($j[0]['ra_afferenza'])){$e.='<br />'.'manca l\'afferenza';}
						if (empty($j[0]['ra_d_afferenza'])){$e.='<br />'.'manca la descrizione dell\'afferenza';}
						if (empty($j[0]['ra_inizio'])){$w.='<br />'.'manca la data di inizio';}
						if (empty($j[0]['ra_fine'])){$e.='<br />'.'manca la data di fine';}
						if (empty($j[0]['ra_cf_referente'])){$w.='<br />'.'manca il referente';}
						if (empty($j[0]['ra_nc_referente'])){$w.='<br />'.'referente non trovato';}
					} else {
						$e.='<br />'.'impossibile leggere l\'account registrato';
					}
				} else {
					$e.='<br />'.'Manca il codice';
				}
				if (!empty($e)){
					$msg.=$e;
					$tit.=' - ERRORE';
					$stl='bg-danger text-center text-white';
					$btn='';
				} else {
					$tit.='Conferma richiesta account';
					if (!empty($w)){
						$msg.=get_alert($w,'warning');
					}
					$msg.='Confermi l\'invio della richiesta di account  di <strong>'.$nome.' '.$cognome.'</strong> ?';
					$stl.='bg-success text-center text-white';
				}
				break;
			case "dra":	//	Cancella richiesta account
				$tit.='Cancella richiesta account';
				$msg.='Confermi la cancellazione della richiesta di account  di <strong>'.$nome.' '.$cognome.'</strong> ?';
				$msg.=get_alert('la cancellazione comporta anche quella degli alias e dei log associati','danger');
				$stl.='bg-danger text-center text-white';
				break;
			case "yra":	//	Approva richiesta account (da richiesta a da_elaborare)
				$tit.='Valida richiesta account';
				$msg.='Confermi la validazione della richiesta di account  di <strong>'.$nome.' '.$cognome.'</strong> ?';
				$stl.='bg-success text-center text-white';
				break;
			case "pra":	//	Stampa richiesta account
				if ($stato != 'completata' and $stato != 'storicizzata'){
					$tit.='Stampa richiesta account';
					$msg.=get_alert('Lo stato della richiesta risulta: '.$stato,'danger');
					$stl.='bg-danger text-center text-white';
					$btn='';
				} else {
					switch ($j[0]['ra_tipo']){
						case "PF":	//	persona fisica
							// $msg=get_pdf_account($nome.' '.$cognome,$j[0]['ra_uid'],$j[0]['ra_prima_password'],$j[0]['ra_ad_princ_mail']);
							$msg=get_pdf_account($j[0]);
							$e=set_ra_log($_REQUEST['k'],"pra - stampa",$stato); // scrivo il log
							break;
						case "GE":	//	generico
							// $msg=get_pdf_account($nome.' '.$cognome,$j[0]['ra_uid'],$j[0]['ra_prima_password'],$j[0]['ra_ad_princ_mail']);
							$msg=get_pdf_account($j[0]);
							$e=set_ra_log($_REQUEST['k'],"pra - stampa",$stato); // scrivo il log
							break;
						case "GU":	//	guest
							// $msg=get_pdf_account_guest($nome,$cognome,$j[0]['ra_uid'],$j[0]['ra_prima_password'],$j[0]['ra_fine'],$j[0]['ra_note'],$j[0]['ra_ad_princ_mail'],$j[0]['ra_cf']);
							$msg=get_pdf_account_guest($j[0]);
							$e=set_ra_log($_REQUEST['k'],"pra - stampa",$stato); // scrivo il log
							break;
					}					
				}
				break;
			case "mra":	//	Invia per email la richiesta account
				$tit.='Invia per email la richiesta account';
				$msg.='Confermi l\'invio dei dati dell\'account di <strong>'.$nome.' '.$cognome.'</strong> all\'indirizzo<br /><strong>'.$mail.'</strong> ?';
				if (!empty($j[0]['ra_notificata'])){ // se ra_notificata==1 warning
					$msg.='<br /><br /><strong class="text-danger">ATTENZIONE:<br />E\' gi&agrave; stato notificato all\'indirizzo '.$mail.' il '.convert_date(substr($j[0]['ra_dt_notifica'],0,10),'yyyy-mm-dd','dd/mm/yyyy').'</strong>';
				}
				$stl.='bg-info text-center text-white';
				break;
			case "sra":	//	Storicizza richiesta account (da completata a storicizzata)
				$tit.='Storicizza richiesta account';
				$msg.='Confermi la storicizzazione della richiesta di account  di <strong>'.$nome.' '.$cognome.'</strong> ?';
				$stl.='bg-success text-center text-white';
				break;
			case "lra":	// log richiesta account
				$msg.='';
				// $sql="select * from storico_ldap.richieste_account_log where ral_ra_k=".$_REQUEST['k'];
				$sql="select ral_d, ral_stato, ral_usr_ins, SUBSTRING(ral_dt_ins, 1, 10) as ral_dt_ins, ral_ip from storico_ldap.richieste_account_log where ral_ra_k=".$_REQUEST['k'];
				$x=array();	
				$msg='';
				$a=mysqli_query($conn_sl,$sql);
				if (empty(mysqli_error($conn_sl))){
					if (mysqli_num_rows($a) > 0) {	// dati trovati
						$msg.='trovate '.mysqli_num_rows($a).' righe<br />';
						foreach($a as $row){
							$x[]=$row;
						}
						$msg.=get_table_data($x,[['ral_d'],['ral_stato'],['ral_usr_ins'],['ral_dt_ins'],['ral_ip']],['descrizione','stato','user','data','ip'],'log',$t='m',false);
					}
				}
				$tit.='Log account di <strong>'.$nome.' '.$cognome.'</strong>';
				$stl.='bg-secondary text-center text-white';
				break;		
		}
		if ($btn=='' and $_REQUEST['type'] != 'lra'){$btn='<button id="modal-ok" class="btn btn-success" data-dismiss="modal" act="get_ra" dom="mm" conferma="y" type="'.$_REQUEST['type'].'" k="'.$_REQUEST['k'].'" tab="na">Conferma</button><button id="modal-ko" class="btn btn-dark" data-dismiss="modal">Annulla</button>';}
	}
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra - prima di preparazione array");}
	$a=array();
	$a['tit']=$tit;
	$a['msg']=$msg;
	// if ($devel) {$a['msg'].=str_replace('","','", "',json_encode($_REQUEST)).'<br />';}
	$a['stl']=$stl;
	$a['btn']=$btn;
	if (empty($e) and $_REQUEST['type']!='lra' and $_REQUEST['type']!='pra' and $_REQUEST['type']!='mra'){
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra - prima di get_ra_stato");}
		if (empty($_REQUEST['k'])){
			$a['domm'][0]['domf']='ga_bottom';	$a['domm'][0]['domc']=get_ra_stato(); // default rileggo la tabella
		} else {
			$a['domm'][0]['domf']='ra_'.$_REQUEST['k'];	$a['domm'][0]['domc']=get_ra_row($_REQUEST['k']); // rileggo la riga
		}
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra - dopo di get_ra_stato");}
		if (!empty($_REQUEST['conferma'])and !empty($_REQUEST['k']) and $_REQUEST['type']!='dra' and $_REQUEST['type']!='era'){
			// aggiorno la riga -> è conferma, non è cancellazione, non è conferma finale
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra - prima di get_ra_row");}
			$a['domm'][0]['domf']='ra_'.$_REQUEST['k'];	$a['domm'][0]['domc']=get_ra_row($_REQUEST['k']);
		}	
	}
	// if ($_REQUEST['type']=='pra' and ( $stato=='storicizzata' or $stato=='completata' or !empty($_REQUEST['conferma']) ) ){ 
	if ($_REQUEST['type']=='pra'){ 
		// stampa
		$s=$msg;
	} else {
		$a['dom']='mm';
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra - prima di json_encode");}
		$s=json_encode($a);
	}
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra - fine");}
	return $s;
}
function dett_ldap_ad($k,$tipo){	// visualizza il dettaglio da LDAP o AD
	global $conn_new, $conn_sl, $ldap_conn, $devel;
	$s='';
	$m='';
	$sql="select * from storico_ldap.richieste_account where ra_k=".$k." or ra_uid='".$k."'";
	$a=mysqli_query($conn_sl,$sql);
	$j=array();
	if (mysqli_num_rows($a) > 0) {	// dati trovati
		foreach($a as $row){
			$j[]=$row;
		}
	}
	if (count($j) > 0){
		$uid=$j[0]['ra_uid'];
		if ($devel){
			if ($j[0]['ra_usr_ins']!='old_storico' and $j[0]['ra_stato']!='storicizzata'){
				$uid='im_'.$uid;
			}
		}
		$o=ldap_authenticate($uid,$j[0]['ra_prima_password'],$tipo);
		// @@@@@@@@@@@@ ricerca anche sugli alias								   
		if (!empty($o['dettaglio'])){
			$m='<div class="colnext alert-primary">Fai click qu&igrave; per vedere il dettaglio '.$tipo.'</div>'.$o['dettaglio'];
		} else {
			if (!empty($o['err'])){$m=$o['err'];}
		}
		$a=array();
		$a['tit']='Dettaglio '.$tipo.' di '.$j[0]['ra_nome'].' '.$j[0]['ra_cognome'];
		$a['msg']=$m;
		$a['dom']='mm';
		$s=json_encode($a);
	}
	return $s;
}
function get_ra_row($k){					// riga richiesta account
	global $conn_new, $conn_sl, $ldap_conn, $devel, $sviluppo_albo;

// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_row - inizio k: $k");}

	$nome='account';
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}	// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}	// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}	// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}	// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict

	$s='';
	$e='';
	// $nc=['ra_k', 'ra_nome', 'ra_cognome', 'ra_mail', 'ra_cf', 'ra_mail_notifica', 'ra_kr', 'ra_afferenza', 'ra_inizio', 'ra_fine', 'ra_cf_referente', 'ra_nc_referente', 'ra_stato', 'ra_uid', 'ra_aliases', 'ra_prima_password', 'ra_usr_ins', 'ra_dt_ins', 'ra_usr_mod', 'ra_dt_mod'];
	$nc=['ra_nome','ra_cognome','ra_cf','ra_fine','ra_cf_referente','ra_stato','ra_uid','ra_prima_password'];
	if ($ict or $su) {$nc=['ra_tipo','ra_nome','ra_cognome','ra_cf','ra_inizio','ra_fine','ra_d_afferenza','ra_stato','ra_uid','ra_aliases','ra_prima_password','ra_usr_ins','ra_dt_ins'];}
	$sql="select * from storico_ldap.richieste_account where ra_k=".$k;
	// $sql.=" order by ra_dt_ins desc, ra_cognome, ra_nome";
	$a=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){
		$e.=get_alert(mysqli_error($conn_sl),'danger');
	}
	$j=array();
	if (mysqli_num_rows($a) > 0) {	// dati trovati
		foreach($a as $row){
			$j[]=$row;
		}
	}
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_row - count: ".count($j));}
	if (count($j) > 0){
		$s.='<td>';
/* 
	- inserimento e conferma dei dati nel form
	STATI ra_stato:
		- bozza = tutti possono modificare richiedere cancellare (utente solo i suoi, (ict e su) su tutti)
		- richiesta = fine editing della richiesta utente aspetta, ict e su possono regredire, validare, cancellare
		- validata_ict = prepara i dati (uid e password) ed eventuali alias controllando storico
		- aspetta_ad = i dati sul db sono completi in attesa di elaborazione dello script IM.ps1 su AD
		- creato_ad = Lo script IM.ps1 su AD ha creato l'account posso creare LDAP
			le registrazioni su LDAP non esiste ancora 
		- completata = elaborazione corretta (può essere stampata, inviata per email, storicizzata) 
			impossibile modificare, solo storicizzare
		- rifiutata = errore di elaborazione, ict e su possono regredire (o regredisce a bozza)
		- storicizzata = account nello storico, non più modificabile (può essere stampata, inviata per email) può essere letta per ripristinare un account vecchio
*/
		$nc[]='note_elaborazione';
		$j[0]['note_elaborazione']='';

		// controllo esistenza e autenticazione su LDAP e AD

		$uid=$j[0]['ra_uid'];
		if ($devel){
			if ($j[0]['ra_usr_ins']!='old_storico' and $j[0]['ra_stato']!='storicizzata'){
				$uid='im_'.$uid;
			}
		}
		
		$congruente=true;
		$uida=$uid;
		$pp=$j[0]['ra_prima_password'];
		$stato=$j[0]['ra_stato'];
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_row - uid: $uid, password: $pp, tipo: 'LDAP', stato: $stato");}		
		$o_ldap=ldap_authenticate($uid,$pp,'LDAP');
		if ($o_ldap['esiste']==0 and $j[0]['ra_aliases'] != ''){
			$aa=explode('|',$j[0]['ra_aliases']);
			foreach($aa as $aai){
				$o_ldap=ldap_authenticate($aai,$j[0]['ra_prima_password'],'LDAP');
				if ($o_ldap['esiste']==1){$uida=$aai; break;}
			}
		}
		$adt=false;
		$o_ad=ldap_authenticate($uid,$j[0]['ra_prima_password'],'AD');
		if ($o_ad['autenticato']==0){
			$adt=true;
			$o_ad=ldap_authenticate($uid,'dsf5672SE!2017','AD');
		}

		if (($o_ldap['esiste']==1 and $o_ad['esiste']==0) or ($o_ldap['esiste']==0 and $o_ad['esiste']==1)
			) {
				// --- STATO INCONGRUO - esiste da una parte e non dall'altra
				if ($o_ldap['esiste']==0){$manca=' LDAP';}
				if ($o_ad['esiste']==0){$manca=' AD';}
				if ($o_ldap['esiste']==1){$esiste=' LDAP';}
				if ($o_ad['esiste']==1){$esiste=' AD';}
				if ($su or $ict){$j[0]['note_elaborazione'].=get_alert('STATO INCONGRUO - <strong>esiste in '.$esiste.' ma non esiste in '.$manca.'</strong>','danger',true);}
				$congruente=false;
		} else {
			if (($o_ldap['autenticato']==1 and $o_ad['autenticato']==0) or ($o_ldap['autenticato']==0 and $o_ad['autenticato']==1)
				) {
					// --- STATO INCONGRUO - le password non sono allineate
					if ($su or $ict){$j[0]['note_elaborazione'].=get_alert('STATO INCONGRUO - <strong>password non allineate</strong>','danger',true);}
					$congruente=false;
			}
		}
		if ($o_ldap['autenticato']==1 and $o_ad['autenticato']==1 and !in_array($j[0]['ra_stato'],array('storicizzata','completata'))
			) {
				// --- STATO INCONGRUO - sia LDAP che AD ci sono ed hanno l'account con la password originale ma lo stato non corrisponde
			if ($su or $ict){
				$j[0]['note_elaborazione'].=get_alert('STATO INCONGRUO -> (esistente ed autenticato sia LDAP che AD) dovrebbe essere almeno <strong>completata</strong>','danger',true);
				$congruente=false;
//  			} else {
//  				try {
//  					$dn='uid='.$uid.',ou=Users,o=sss,c=it';
//  					$sql="update storico_ldap.richieste_account set ra_stato = 'completata', ra_ldap_dn_ini='".$dn."' where ra_k = ".$j[0]['ra_k'];
//  					$a=mysqli_query($conn_sl,$sql);
//  					if (!empty(mysqli_error($conn_sl))){
//  						$j[0]['note_elaborazione'].=mysqli_error($conn_sl);
//  						$congruente=false;
//  					} else {
//  						$j[0]['ra_stato']='completata';
//  						$j[0]['note_elaborazione'].='Adeguato stato';
//  					}
//  				} catch (Exception $ee) {
//  					if ($ee!=''){$e.='<br />Impossibile aggiornare Mysql - '.$ee;}
//  					if ($su){$j[0]['note_elaborazione'].=get_alert('STATO INCONGRUO -> dovrebbe essere almeno <strong>completata</strong>','danger',true);}
//  					$congruente=false;
//  				}
//  			}
			}
		}
		if (($o_ldap['esiste']==0 or $o_ad['esiste']==0) and in_array($j[0]['ra_stato'],array('storicizzata','completata'))
			) {
				// --- STATO INCONGRUO - non esiste in LDAP o in AD e lo stato invece è completato o storicizzato
				$manca='';
				if ($o_ldap['esiste']==0){$manca.=' LDAP';}
				if ($o_ad['esiste']==0){$manca.=' AD';}
				if ($su or $ict){$j[0]['note_elaborazione'].=get_alert('STATO <strong>'.$j[0]['ra_stato'].'</strong> INCONGRUO - manca '.$manca,'danger',true);}
				$congruente=false;
		}

		if ($su and false){	 // debug
			$j[0]['note_elaborazione'].='stato: '.$j[0]['ra_stato'].'<br>';
			$j[0]['note_elaborazione'].='ra_modifica_password: '.$j[0]['ra_modifica_password'].'<br>';
			$j[0]['note_elaborazione'].='ra_uid_modifica_password: '.$j[0]['ra_uid_modifica_password'].'<br>';
			$j[0]['note_elaborazione'].='esiste in LDAP: '.$o_ldap['esiste'].'<br>';
		}

		// cambio la password
		if ($j[0]['ra_stato']=='aspetta_ldap_cgp' and $j[0]['ra_modifica_password']!='' and $j[0]['ra_uid_modifica_password']!='' and $o_ldap['esiste']==1){

			$j[0]['note_elaborazione'].=get_alert('cambio password','warning');

			$p=$j[0]['ra_modifica_password'];
			$uidcp=$j[0]['ra_uid_modifica_password'];
			$o1_ad=ldap_authenticate($uidcp,$p,'AD');
			if ($o1_ad['autenticato']==1){
				// lo script AD ha cambiato la password - cambio la password su LDAP
				$info=array();
				$dn='uid='.$uidcp.',ou=Users,o=sss,c=it';
				$info["userPassword"]="$p"; // ok
				// $info["sambaNTPassword"]=LMHash($p);
				ldap_modify($ldap_conn, $dn, $info);
				$e.=get_ldap_error('','ldap_modify: ');
				if ($e==''){
					// riprovo l'autenticazione 
					$o1_ldap=ldap_authenticate($uidcp,$p,'LDAP');
					if ($o1_ldap['autenticato']==1) {
						// mi sono autenticato con la nuova password sia su AD che su LDAP
						$o_ldap=$o1_ldap;
						$o_ad=$o1_ad;
						try {
							// riporto lo stato a completata
							// cancello la nuova password (potrei lasciarla tanto viene elaborata solo se lo stato è aspetta_ad_cgp)
							$sql="update storico_ldap.richieste_account set ra_stato = 'completata', ra_uid_modifica_password = '', ra_modifica_password = '' where ra_k = ".$j[0]['ra_k'];
							$a=mysqli_query($conn_sl,$sql);						
							if (!empty(mysqli_error($conn_sl))){
								$j[0]['note_elaborazione'].=mysqli_error($conn_sl);
							} else {
								$j[0]['ra_stato']='completata';
								$j[0]['note_elaborazione'].='Password modificata';
								// crea log completamento
								$e=set_ra_log($j[0]['ra_k'],"cgp - Password modificata",'completata'); // scrivo il log						
							}
						} catch (Exception $ee) {
							if ($ee!=''){$e.='<br />Impossibile aggiornare MariaDB - '.$ee;}
						}
					}
				}					
			}
			$j[0]['note_elaborazione'].=$e;
		}

		// crea LDAP
		if (
			($j[0]['ra_stato']=='creato_ad' and $o_ldap['esiste']==0 and $o_ad['autenticato']==1)
			or
			($j[0]['ra_stato']=='forza_ldap' and $o_ldap['esiste']==0)
			){
			// su AD è tutto ok LDAP non esiste
			$e='';
			$j[0]['note_elaborazione']='';
			
			$p=$j[0]['ra_prima_password'];

			// --- preparazione dati per LDAP
			$info=array();
			$dn='uid='.$uid.',ou=Users,o=sss,c=it';
			$info["objectClass"][]="inetOrgPerson";
			$info["objectClass"][]="organizationalPerson";
			$info["objectClass"][]="posixAccount";
			$info["objectClass"][]="shadowAccount";
			$info["objectClass"][]="sambaSamAccount";
			$info["objectClass"][]="x-person";
			$info["objectClass"][]="radiusprofile";
			$info["objectClass"][]="person";
			$info["objectClass"][]="top";
			$info["objectClass"][]="schacEmployeeInfo";

			$info["uid"]=$uid;
			$info["userPassword"]="$p"; // ok
			// $info['userPassword'] = '{MD5}'."base64_encode(pack('H*', md5('".$j[0]['ra_prima_password']."')))";
//			$a=getNextSerial("ou=GuestUsers,o=sss,c=it","x-seriale"); // cattura next guest
			$a=getNextSerial("cn=NextFreeUnixId,o=sss,c=it","uidNumber");
			// $a=getNextSerial("sambaDomainName=SSS","sambaNextRid");
			$info["sambaSID"]="S-1-5-21-3475812762-2383038380-3056063006-".$a['n'];
			$info["sambaNTPassword"]=LMHash($j[0]['ra_prima_password']);
			$info["sambaAcctFlags"]="[UX]";
			$info["sambaKickoffTime"]="2147483647";
			// $info["sambaLMPassword"]="F3492FE33943AB35D6C10F1ED325FB21"; // obsoleto
			$info["sambaLogoffTime"]="2147483647";
			$info["sambaPwdMustChange"]="2147483647";
			$info["sambaLogonTime"]="0";
			$info["sambaPrimaryGroupSID"]="S-1-5-21-3475812762-2383038380-3056063006-513"; // vedi gidNumber
			$info["sambaPwdCanChange"]="0";
			$info["sambaPwdLastSet"]="0";
			$info["uidNumber"]=$a['n'].'';
			$info["homeDirectory"]="/home/nobody";  // nobody 
			// $info["displayName"]=$_REQUEST['ldap_sn']." ".$_REQUEST['ldap_givenname'];  
			$info["loginShell"]="/bin/nologin";  
			
			/*
			Il seriale per gidNumber si usa quando si creano nuovi gruppi.
			Per gli User è il valore costante 513  (514 per i Guest)
			*/
			// $a=getNextSerial("cn=NextFreeUnixId,o=sss,c=it","gidNumber");
			$info["gidNumber"]='513';	// $a['n'];  
			$info["gecos"]="User";  
			
			$info["sn"]=$j[0]['ra_cognome'];
			$info["givenname"]=$j[0]['ra_nome'];
			$info["cn"]=$j[0]['ra_nome'].' '.$j[0]['ra_cognome'];
			$info["description"]=$j[0]['ra_nome'].' '.$j[0]['ra_cognome'];
			$info["displayName"]=$j[0]['ra_nome'].' '.$j[0]['ra_cognome'];
			$info["mail"]=strtolower($j[0]['ra_mail']);
			if (!empty($j[0]['ra_ad_princ_mail'])){$info["mail"]=strtolower($j[0]['ra_ad_princ_mail']);}
			// $info["telephonenumber"]=$$j[0]['ldap_telephonenumber'];
			// $info["mobile"]=$$j[0]['ldap_mobile'];

			if (!empty($j[0]['ra_afferenza'])){
				$info["departmentnumber"]=$j[0]['ra_afferenza'];
			}
			if (!empty($j[0]['ra_d_afferenza'])){
				$info["ou"]=$j[0]['ra_d_afferenza'];
			}
			if (!empty($j[0]['ra_ruolo'])){
				$info["businesscategory"]=$j[0]['ra_ruolo'];
			}

			
			$info["x-codiceFiscale"]=$j[0]['ra_cf'];
			$df=str_replace('-','',$j[0]['ra_fine']);
			$info["x-scadenzaTempo"]=$df.'220000.515Z';

			// $s.='<hr>'.print_r($info,true);
			// $j=json_encode($info);
			// $s.='<hr>'.$j;

			if (!$ldap_conn) {
				// è già una anomalia ma provo a riconnettermi
				$ip_ldap = '192.168.64.11';
				$ip_ldap_devel = '192.168.64.6';
				if ($devel) {$ip_ldap=$ip_ldap_devel;}
				$ldap_conn = ldap_connect($ip_ldap,389);
				$e.=get_ldap_error('','Connessione LDAP: ');
				if ($e==''){
					ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
					ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
				}
			}
			if ($e==''){
				$r = ldap_add($ldap_conn, $dn, $info);
				// $r = ldap_add_ext($ldap_conn, $info["dn"], $info);
				$e.=get_ldap_error('','ldap_add: ');
			}
			if ($e==''){
				// ribadisco la nuova password in LDAP
				$info = array();
				$info["userPassword"] = "$p";
				ldap_modify($ldap_conn, $dn, $info);
				$e.=get_ldap_error('','ldap_modify: ');
			}

			if ($e==''){
				// riprovo l'autenticazione 
				$o1_ldap=ldap_authenticate($uid,$j[0]['ra_prima_password'],'LDAP');
				if ($o1_ldap['autenticato']==1) {
					$o_ldap=$o1_ldap;
					try {
						$sql="update storico_ldap.richieste_account set ra_stato = 'completata', ra_ldap_dn_ini='".$dn."' where ra_k = ".$j[0]['ra_k'];
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_row - sql: ".$sql);}
						$a=mysqli_query($conn_sl,$sql);						
						if (!empty(mysqli_error($conn_sl))){$j[0]['note_elaborazione'].=mysqli_error($conn_sl);}
						$j[0]['ra_stato']='completata';
						$j[0]['note_elaborazione'].='Creato LDAP';
						// crea log completamento
						$e=set_ra_log($j[0]['ra_k'],"fra - completa con scrittura su LDAP",'completata'); // scrivo il log						
					} catch (Exception $ee) {
						if ($ee!=''){$e.='<br />Impossibile aggiornare Mysql - '.$ee;}
					}
				} else {
					$e.='<br />Impossibile leggere/autenticarsi con il nuovo account appena scritto in LDAP';
				}
			}
			if ($e!=''){
				$j[0]['note_elaborazione'].=$e;
			} else {
				// crea il forward su COMMUNIGATE (ra_ad_princ_mail)
				// su @alumnisssup.mail.onmicrosoft.com
				// if (!empty($j[0]['ra_ad_princ_mail'])){
				if (!empty($j[0]['ra_ad_princ_mail'])){
					$c='ABCreaForwarder '.strtolower(reset(explode('@',$j[0]['ra_ad_princ_mail'])));
					shell_exec('/usr/bin/perl AB.pl '.$c);
				}
			}
		} 
		
		if ($o_ldap['autenticato']==1 and $o_ldap['esiste']==1){
			$j[0]['note_elaborazione'].=get_alert('esiste in LDAP con password originale','success',true);
		}
		if ($o_ldap['autenticato']==0 and $o_ldap['esiste']==1){
			$j[0]['note_elaborazione'].=get_alert('esiste in LDAP con password cambiata','warning',true);
			if ($su and !empty($o_ldap['err'])){$j[0]['note_elaborazione'].=$o_ldap['err'].'<br />';}
		}
		if ($o_ldap['esiste']==0){
			$j[0]['note_elaborazione'].=get_alert('NON esiste in LDAP','danger',true);
			if ($su and !empty($o_ldap['err'])){$j[0]['note_elaborazione'].=$o_ldap['err'].'<br />';}
		}
		if ($o_ad['autenticato']==1 and $o_ad['esiste']==1){
			$j[0]['note_elaborazione'].=get_alert('esiste in AD con password'.($adt?' temporanea':' originale'),'success',true);
		}
		if ($o_ad['autenticato']==0 and $o_ad['esiste']==1){
			$j[0]['note_elaborazione'].=get_alert('esiste in AD con password cambiata','warning',true);
			if ($su and !empty($o_ad['err'])){$j[0]['note_elaborazione'].=$o_ad['err'].'<br />';}
		}
		if ($o_ad['esiste']==0){
			$j[0]['note_elaborazione'].=get_alert('NON esiste in AD','danger',true);
			if ($su and !empty($o_ad['err'])){$j[0]['note_elaborazione'].=$o_ad['err'].'<br />';}
		}
		
		$b=array(
			  'cra'=>false 				// conferma
			, 'ura'=>false 				// modifica
			, 'dra'=>false 				// cancella
			, 'yra'=>false 				// valida
			, 'bra'=>false     		// back - torna a bozza
			, 'era'=>false  			// prepara
			, 'pra'=>false   			// stampa
			, 'mra'=>false    		// invia per email
			, 'sra'=>false  			// storicizza
			, 'lra'=>false      	// visualizza log
			, 'dett_ldap'=>false	// visualizza dettaglio LDAP
			, 'dett_ad'=>false  	// visualizza dettaglio AD
			, 'dett_can'=>false  	// visualizza dettaglio AB_CAN
		);
		
		if ($j[0]['ra_stato']=='bozza'){
			if (!$b['cra']){$s.='<button class="btn btn-outline-success btn-sm" title="conferma richiesta account" dom="mm" type="cra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-check"></i></button>'; $b['cra']=true;}
			if (!$b['ura']){$s.='<button class="btn btn-outline-info btn-sm" title="modifica richiesta account" dom="mm" type="ura" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-pen"></i></button>'; $b['ura']=true;}
			if (!$b['dra']){$s.='<button class="btn btn-outline-danger btn-sm" title="cancella richiesta account" dom="mm" type="dra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-trash"></i></button>'; $b['dra']=true;}
		}
		if ($j[0]['ra_stato']=='rifiutata'){
			if (!$b['ura']){$s.='<button class="btn btn-outline-info btn-sm" title="modifica richiesta account" dom="mm" type="ura" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-pen"></i></button>'; $b['ura']=true;}
			if (!$b['bra']){$s.='<button class="btn btn-outline-primary btn-sm" title="riporta richiesta account a bozza" dom="mm" type="bra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fas fa-undo"></i></button>'; $b['bra']=true;}
			if (!$b['dra']){$s.='<button class="btn btn-outline-danger btn-sm" title="cancella richiesta account" dom="mm" type="dra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-trash"></i></button>'; $b['dra']=true;}
		}
		if ($j[0]['ra_stato']=='richiesta' and ($su or $ict)){
			if (!$b['yra']){$s.='<button class="btn btn-outline-success btn-sm" title="valida richiesta account" dom="mm" type="yra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-check-double"></i></button>'; $b['yra']=true;}
			if (!$b['bra']){$s.='<button class="btn btn-outline-primary btn-sm" title="riporta richiesta account a bozza" dom="mm" type="bra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fas fa-undo"></i></button>'; $b['bra']=true;}
			if (!$b['dra']){$s.='<button class="btn btn-outline-danger btn-sm" title="cancella richiesta account" dom="mm" type="dra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-trash"></i></button>'; $b['dra']=true;}
			if (!$b['ura']){$s.='<button class="btn btn-outline-info btn-sm" title="modifica richiesta account" dom="mm" type="ura" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-pen"></i></button>'; $b['ura']=true;}
		}
		if ($j[0]['ra_stato']=='validata_ict' and ($su or $ict)){
			if (!$b['era']){$s.='<button class="btn btn-outline-warning btn-sm" title="elaborazione (prepara)" dom="mm" type="era" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fas fa-cog"></i></button>'; $b['era']=true;}
			if (!$b['bra']){$s.='<button class="btn btn-outline-primary btn-sm" title="riporta richiesta account a bozza" dom="mm" type="bra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fas fa-undo"></i></button>'; $b['bra']=true;}
			if (!$b['dra']){$s.='<button class="btn btn-outline-danger btn-sm" title="cancella richiesta account" dom="mm" type="dra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-trash"></i></button>'; $b['dra']=true;}
		}
		// if ($j[0]['ra_stato']=='aspetta_ad'){ ... }
		// if ($j[0]['ra_stato']=='creato_ad'){ ... }
		
		if ($j[0]['ra_stato']=='completata' and ($o_ldap['esiste']==1 and $o_ad['esiste']==1)){
			if (!$b['pra']){$s.='<button class="btn btn-outline-info btn-sm" title="stampa richiesta account" dom="dd" type="pra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-print"></i></button>'; $b['pra']=true;}
			if ($j[0]['ra_mail_notifica']!=''){
				if (!$b['mra']){$s.='<button class="btn btn-outline-info btn-sm" title="invia richiesta account" dom="mm" type="mra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-at"></i></button>'; $b['mra']=true;}
			}
			if (!$b['sra']){$s.='<button class="btn btn-outline-dark btn-sm" title="storicizza richiesta account" dom="mm" type="sra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="far fa-folder"></i></button>'; $b['sra']=true;}
		}
		if ($j[0]['ra_stato']=='storicizzata' and ($o_ldap['esiste']==1 and $o_ad['esiste']==1)){
			if (!$b['pra']){$s.='<button class="btn btn-outline-info btn-sm" title="stampa richiesta account" dom="dd" type="pra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-print"></i></button>'; $b['pra']=true;}
			if ($j[0]['ra_mail_notifica']!=''){
				if (!$b['mra']){$s.='<button class="btn btn-outline-info btn-sm" title="invia richiesta account" dom="mm" type="mra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-at"></i></button>'; $b['mra']=true;}
			}
		}
		if ($su or $ict){
			// vedo i log e se esistono i dettagli LDAP, AD e CAN
			if ($j[0]['ra_stato']!='bozza'){
				if (!$b['lra']){$s.='<button class="btn btn-outline-secondary btn-sm" title="log" dom="mm" type="lra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fas fa-clipboard-list"></i></button>'; $b['lra']=true;}
			}
			if ($o_ldap['esiste']==1){
				if (!$b['dett_ldap']){$s.='<button class="btn btn-outline-warning btn-sm" title="LDAP" dom="mm" act="dett_ldap" k="'.$j[0]['ra_k'].'"><i class="far fa-folder-open"></i></button>'; $b['dett_ldap']=true;}
			}
			if ($o_ad['esiste']==1){
				if (!$b['dett_ad']){$s.='<button class="btn btn-outline-primary btn-sm" title="AD" dom="mm" act="dett_ad" k="'.$j[0]['ra_k'].'"><i class="far fa-folder-open"></i></i></button>'; $b['dett_ad']=true;}
			}
			if (($o_ldap['esiste']==0 and $o_ad['esiste']==0) and ($j[0]['ra_stato']=='storicizzata' or $j[0]['ra_stato']=='completata')) {
				if (!$b['bra']){$s.='<button class="btn btn-outline-primary btn-sm" title="riporta richiesta account a bozza" dom="mm" type="bra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fas fa-undo"></i></button>'; $b['bra']=true;}
				if ($j[0]['ra_cancellabile']==1){
					if (!$b['dra']){$s.='<button class="btn btn-outline-danger btn-sm" title="cancella richiesta account" dom="mm" type="dra" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-trash"></i></button>'; $b['dra']=true;}
				}
			}
			if ($su or $ict){
				if (!$b['ura']){$s.='<button class="btn btn-outline-info btn-sm" title="modifica richiesta account" dom="mm" type="ura" act="get_ra" k="'.$j[0]['ra_k'].'"><i class="fa fa-pen"></i></button>'; $b['ura']=true;}
			}
			
			if ($j[0]['ra_uid'] != ''){
				// cerco in ab_can, se esiste visualizzo il pulsante per vedere il dettaglio (per tutti)
				$in_can=array_search($j[0]['ra_uid'],$_SESSION['IAM']['ab_can']['LDAP_UID']);
				if ($in_can !== false){
					if (!$b['dett_can']){$s.='<button class="btn btn-outline-success btn-sm" title="Carriera attiva" dom="mm" act="dett_can" k="'.$j[0]['ra_k'].'" uid="'.$j[0]['ra_uid'].'"><i class="far fa-folder-open"></i></button>'; $b['dett_can']=true;}
				}
			}
		}
		$s.='</td>';
		foreach($j[0] as $nf => $f){
			if (in_array($nf,$nc)){
				$ss='<td>'.$f.'</td>';
				if ($nf=='ra_inizio'){$ss='<td>'.substr($f,0,10).'</td>';}
				if ($nf=='ra_fine'){$ss='<td>'.substr($f,0,10).'</td>';}
				if ($nf=='ra_stato'){
					$ss='<td id="ra_stato_'.$j[0]['ra_k'].'"';
					if ($j[0]['ra_motivo'] != '' or $j[0]['ra_ad_motivo'] != ''){
						$ss.=' class="alert-danger" title="'.trim($j[0]['ra_motivo'].' '.$j[0]['ra_ad_motivo']).'"';
					}
					$ss.='>'.$f.'</td>';
				}
				if ($nf=='ra_cf_referente'){
					$tmp='';
					$x=array_search($f,$_SESSION['IAM']['ab_can']['COD_FISC']);
					if ($x!==false){$tmp=$_SESSION['IAM']['ab_can']['COGNOME'][$x].' '.$_SESSION['IAM']['ab_can']['NOME'][$x];}
					$ss='<td>'.$tmp.'</td>';
				}
				$s.=$ss;
			}
		}
	}
	return $s;
}
function get_ra_stato(){					// tabella delle richieste di account
	global $conn_new, $conn_sl, $devel, $sviluppo_albo;
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_stato - inizio");}
	$nome='account';
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}	// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}	// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}	// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}	// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict

	$s=""; $w='';
	
	// Richieste Account
	$nc=['ra_nome','ra_cognome','ra_cf','ra_fine','ra_nc_referente','ra_stato','ra_uid','ra_prima_password'];
	if ($ict) {$nc=['ra_tipo','ra_nome','ra_cognome','ra_cf','ra_inizio','ra_fine','ra_d_afferenza','ra_stato','ra_uid','ra_aliases','ra_prima_password','ra_usr_ins','ra_dt_ins'];}
	if ($su) {$nc=['ra_tipo','ra_nome','ra_cognome','ra_cf','ra_inizio','ra_fine','ra_d_afferenza','ra_stato','ra_uid','ra_aliases','ra_prima_password','ra_usr_ins','ra_dt_ins'];}

	$sql="select ra.*, ras.ras_ord_01 from storico_ldap.richieste_account as ra left join storico_ldap.richieste_account_stati as ras on ra.ra_stato = ras.ras_stato";

	$from=date("Y-m-d",strtotime("-1 week"));	// $ultima_settimana = date("Y-m-d",strtotime("-1 week"));
	if ($ict or $su){	// ict o supeuser
		if (!empty($_REQUEST['account_from'])){
			$from=convert_date($_REQUEST['account_from'],'dd/mm/yyyy','yyyy-mm-dd');
			if ($w!=''){$w.=' and ';}
			$w="(ra_inizio >= '".$from."')";
		} 
		if (!empty($_REQUEST['account_find'])){
			$f=strtolower($_REQUEST['account_find']);
			$ww="( lower(ra_nome) like '%".$f."%'";
			$ww.=" or lower(ra_cognome) like '%".$f."%'";
			$ww.=" or lower(ra_uid) like '%".$f."%'";
			$ww.=" or lower(ra_cf) like '%".$f."%'";
			$ww.=")"; 
			if ($w!=''){$w.=" and ";}
			$w.=$ww;
		}
		if ($w==''){ // senza scelte visualizzo i record da completare
			$w="(ra_stato <> 'storicizzata')";
		}
	} else {
		// utenti normali
		$w="(ra_stato <> 'storicizzata') and (ra_usr_ins='".$_SESSION['IAM']['uid_login']."')";
		if (empty($_REQUEST['account_from'])){
			$from=date("Y-m-d",strtotime("-1 week"));	// $ultima_settimana = date("Y-m-d",strtotime("-1 week"));
			if ($w!=''){$w.=' and ';}
			$w.=" and (ra_inizio >= '".$from."')";
		}
	}
	// if(!empty($_REQUEST['type'])){if ($_REQUEST['type']=='old'){$ww="";}}
	if ($w!=''){
		$sql.=" where (".$w.')';
		if (($su or $ict) and empty($_REQUEST['account_find'])){
			$sql.=" or ra_stato <> 'storicizzata'";
		}	
	}
	$sql.=" order by ras_ord_01, ra_dt_ins desc, ra_cognome, ra_nome";
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_stato - sql: ".$sql);}
	if ($su) {$s.=get_alert($sql);}
	// tolog('MYSQL: '.$sql);
	$j=array();
	$a=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){
		$s.='<br />'.mysqli_error($conn_sl);
		return $s;
	}
	if (mysqli_num_rows($a) > 0) {	// dati trovati
		foreach($a as $row){
			$j[]=$row;
		}
	}
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_stato - count: ".count($j));}
	$m='';
	if ($devel and $su){$m=' - DEVEL';}
	if ($su or $ict) {$s.=get_alert('trovati '.count($j).' records'.$m,'secondary py-2 text-center');}
	else if ($ut) {$s.=get_alert('trovati '.count($j).' records da te inseriti','secondary py-2 text-center');}

	if (count($j) > 0){
		// tabella richieste
		$n='tb-richieste-account';

		$s.='<div class="row bg-light">';
			$s.='<div class="col-sm-2">';
				// if ($dtb){
					$s.='<button class="btn btn-info btn-sm btn-block" act="toggleDTX" tabledom="'.$n.'">Toggle DataTables</button>';
				// }
			$s.='</div>';
			$s.='<div class="col-sm-2">';
//				crea_csv($a,$g,$d,$n);
//				$nf=str_replace("-","_",'tables/'.$_SESSION['IAM']['uid_login'].'_'.$n.'.csv');
//				$s.='<a class="btn btn-success btn-sm btn-block" href="'.$nf.'" target="_blank">CSV</a>';
			$s.='</div>';
			$s.='<div class="col-sm-2">';
			$s.='</div>';
			$s.='<div id="alert-'.$n.'" class="col-sm-6"></div>';
		$s.='</div>';
		
		$s.='<table class="table table-striped table-sm table-responsive" id="'.$n.'"><thead><tr>';
		$s.='<th></th>';	// azioni
		$af=array_keys($j[0]);
		foreach($af as $f){
			if (in_array($f,$nc)){
				$s.='<th>'.substr($f,3).'</th>'; // tolgo 'ra_'
			}
		}
		$s.='<th>elab</th>';
		$s.='</tr></thead><tbody>';
		foreach($j as $row){
			$c='';
			if ($row['ra_stato']=='rifiutata'){$c='text-danger';}
			if ($row['ra_stato']=='storicizzata'){$c='text-info';}
			$s.='<tr k="'.$row['ra_k'].'" id="ra_'.$row['ra_k'].'" class="'.$c.'">';
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_stato - prima get_ra_row");}
				$s.=get_ra_row($row['ra_k']);
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_stato - dopo get_ra_row");}
			$s.='</tr>';	
		}
		$s.='</tbosy></table>';
	}
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_stato - fine");}
	return $s;
}
function get_ra_na(){ 						// form di inserimento dati per un nuovo account
	global $conn_new, $ldap_conn, $conn_sl, $sviluppo_albo;

	$nome='account';
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}	// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}	// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}	// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}	// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict

	if ($_REQUEST['func']=='get_e_ra'){$d=' disabled';} else {$d='';}
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_na - inizio");}
	$j=array();
	if (!empty($_REQUEST['k'])){
		$sql="select * from storico_ldap.richieste_account where ra_k='".$_REQUEST['k']."'";
		$a=mysqli_query($conn_sl,$sql);
		if (mysqli_num_rows($a) > 0) {	// dati trovati
			foreach($a as $row){
				$j[]=$row;
			}
		}
	}	
	// ra_k, ra_tipo, ra_nome, ra_cognome, ra_mail, ra_cf, ra_mail_notifica, ra_notificata, ra_dt_notifica, ra_ruolo, ra_afferenza, ra_d_afferenza, ra_inquadramento, ra_inizio, ra_nuovo_inizio, ra_fine, ra_cf_referente, ra_nc_referente, ra_stato, ra_motivo, ra_uid, ra_aliases, ra_redirect, ra_prima_password, ra_modifica_password, ra_cancellabile, ra_note, ra_nfcm, ra_ldap_dn_ini, ra_ad_motivo, ra_ad_princ_mail, ra_ad_dn_ini, ra_usr_ins, ra_dt_ins, ra_ip_ins, ra_usr_mod, ra_dt_mod, ra_ip_mod							
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_na - dopo lettura record");}
	$s='<br />';

	$s.='<form id="f_na" class="">';
	
		$s.='<div class="row alert alert-dark">';
			$s.='<div class="col-sm-12">';
			
				$s.='<div class="row bg-light py-1">';
					$v='PF';	// default
					// if (!empty($_REQUEST['snac'])){$v=$_REQUEST['snac'];} else {$v='';}	// @@@ ???
					// if (!empty($_REQUEST['snac'])){$v=$_REQUEST['snac'];}
					if (!empty($j[0]['ra_tipo'])){$v=$j[0]['ra_tipo'];} 
					$s.='<div class="col-sm-4 text-right">Tipo account (Account type)</div>';
					$s.='<div class="col-sm-8 text-right">';
						// $s.='<input type="text" name="tipo_account" id="tipo_account" value="'.$v.'" class="form-control form-control-sm" />';
						$s.='<select name="tipo_account" id="tipo_account" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep '.$d.'>';
						$s.='<option class="alert-success" data-tokens="PF" value="PF" '.($v=='PF'?'selected':'').'><span>Persona fisica</span></option>';
						if ($su or $ict){
							$s.='<option class="alert-warning" data-tokens="GE" value="GE" '.($v=='GE'?'selected':'').'><span>Generico</span></option>';
							$s.='<option class="alert-danger" data-tokens="GU" value="GU" '.($v=='GU'?'selected':'').'><span>Guest</span></option>';
						}
						$s.='</select>';
					$s.='</div>';
				$s.='</div>';

				$s.=get_alert('<strong>Dati anagrafici / Personal data</strong>','secondary text-center');
				$s.='<div class="row bg-light py-1">';
					$s.='<div class="col-sm-4 text-right">Nome (First name):</div>';
					$s.='<div class="col-sm-8">';
						if (!empty($j[0]['ra_nome'])){$v=$j[0]['ra_nome'];} else {$v='';}
						$s.='<input type="text" name="nome" id="nome" class="form-control form-control-sm" value="'.$v.'" '.$d.' />';
					$s.='</div>';
				$s.='</div>';
				$s.='<div class="row bg-light py-1">';
					$s.='<div class="col-sm-4 text-right">Cognome o denominazione (Family/Last):</div>';
					$s.='<div class="col-sm-8">';
						if (!empty($j[0]['ra_cognome'])){$v=$j[0]['ra_cognome'];} else {$v='';}
						$s.='<input type="text" name="cognome" id="cognome" class="form-control form-control-sm" value="'.$v.'" '.$d.' />';
					$s.='</div>';
				$s.='</div>';
				$s.='<div class="row bg-light py-1">';
					$s.='<div class="col-sm-4 text-right">E-mail di notifica (Notification email):</div>';
					$s.='<div class="col-sm-8">';
						if (!empty($j[0]['ra_mail_notifica'])){$v=$j[0]['ra_mail_notifica'];} else {$v='';}
						$s.='<input placeholder="inserisci la/le mail a cui notificare il pdf con i dati dell\'account" type="text" name="email_ex" id="email_ex" class="form-control form-control-sm" value="'.$v.'" '.$d.' />';
					$s.='</div>';
				$s.='</div>';
				$s.='<div class="row bg-light py-1">';
					$s.='<div class="col-sm-4 text-right">Codice fiscale (Italian tax code):</div>';
					$s.='<div class="col-sm-8">';
						if (!empty($j[0]['ra_cf'])){$v=$j[0]['ra_cf'];} else {$v='';}
						$s.='<input type="text" name="cf" id="cf" class="form-control form-control-sm" value="'.$v.'" '.$d.' />';
					$s.='</div>';
				$s.='</div><br />';
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_na - prima di select afferenza");}
				$s.=get_alert('<strong>Dati del contratto di lavoro o del periodo di studio / employment contract or study period data</strong>','secondary text-center');
				$s.='<div class="row bg-light py-1">';
					$s.='<div class="col-sm-4 text-right">Struttura (Department):</div>';
					$s.='<div class="col-sm-8">';
						if (!empty($j[0]['ra_afferenza'])){$v=$j[0]['ra_afferenza'];} else {$v='';}
						$ss='';
						if (!empty($_SESSION['IAM']['lista_afferenze'])){
							for ($y=0; $y < count($_SESSION['IAM']['lista_afferenze']['K']); $y++) {
								$ss.='<option data-tokens="'.$_SESSION['IAM']['lista_afferenze']['K'][$y].'" value="'.$_SESSION['IAM']['lista_afferenze']['K'][$y].'"';
								if ($_SESSION['IAM']['lista_afferenze']['K'][$y]==$v){$ss.=' selected';}
								$ss.='>'.$_SESSION['IAM']['lista_afferenze']['D'][$y].'</option>';
							}
						}
						$s.='<select name="ka" id="ka" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep '.$d.'><option data-tokens="0" value="0"></option>'.$ss.'</select>';
					$s.='</div>';
				$s.='</div>';
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_na - prima di select ruolo");}
				$s.='<div class="row bg-light py-1">';
					$s.='<div class="col-sm-4 text-right">Categoria (Category):</div>';
					$s.='<div class="col-sm-8">';
						if (!empty($j[0]['ra_ruolo'])){$v=$j[0]['ra_ruolo'];} else {$v='';}
						$ss='';
						if (!empty($_SESSION['IAM']['lista_ruoli'])){
							for ($y=0; $y < count($_SESSION['IAM']['lista_ruoli']['K']); $y++) {
								$ss.='<option data-tokens="'.$_SESSION['IAM']['lista_ruoli']['K'][$y].'" value="'.$_SESSION['IAM']['lista_ruoli']['K'][$y].'"';
								if ($_SESSION['IAM']['lista_ruoli']['K'][$y]==$v){$ss.=' selected';}
								$ss.='>'.$_SESSION['IAM']['lista_ruoli']['D'][$y].'</option>';
							}
						}
						$s.='<select name="kr" id="kr" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep '.$d.'><option data-tokens="0" value="0"></option>'.$ss.'</select>';
					$s.='</div>';
				$s.='</div><br />';

				$s.=get_alert('<strong>Solo per Esterni o contratti a termine (external member or contractor only)</strong>','danger text-center');
				$s.='<div class="row bg-light py-1">';
					$s.='<div class="col-sm-4 text-right">Data inizio (start date):</div>';
					$s.='<div class="col-sm-8">';
						if (!empty($j[0]['ra_inizio'])){$v=convert_date(substr($j[0]['ra_inizio'],0,10),'yyyy-mm-dd','dd/mm/yyyy');} else {$v=date("d/m/Y");}
						
						$s.='<input type="text" name="di" id="di" class="form-control form-control-sm" value="'.$v.'" dto '.$d.' />';
					$s.='</div>';
				$s.='</div>';
				$s.='<div class="row bg-light py-1">';
					$s.='<div class="col-sm-4 text-right">Data fine (end date):</div>';
					$s.='<div class="col-sm-8">';
						if (!empty($j[0]['ra_fine'])){
							$v=convert_date(substr($j[0]['ra_fine'],0,10),'yyyy-mm-dd','dd/mm/yyyy');
						} else {
							$nd=date('Y-m-d', strtotime('+60 days'));
							$v=date_format(date_create($nd),"d/m/Y");
						}
						$s.='<input type="text" name="df" id="df" class="form-control form-control-sm" value="'.$v.'" dto '.$d.' />';
					$s.='</div>';
/*
					$s.='<div class="col-sm-8">';
						if (!empty($j[0]['ra_fine'])){$v=convert_date(substr($j[0]['ra_fine'],0,10),'yyyy-mm-dd','dd/mm/yyyy');} else {$v='02/02/2222';}
						$s.='<input type="text" name="df" id="df" class="form-control form-control-sm" value="'.$v.'" dto '.$d.' />';
					$s.='</div>';
*/					
				$s.='</div>';
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_na - prima di select Referente");}
				$s.='<div class="row bg-light py-1">';
					$s.='<div class="col-sm-4 text-right">Referente Interno (Internal Referee):</div>';
					$s.='<div class="col-sm-8">';
						if (!empty($j[0]['ra_cf_referente'])){$v=$j[0]['ra_cf_referente'];} else {$v='';}
						$ss='';
						if (!empty($_SESSION['IAM']['ab_can'])){
							$a=$_SESSION['IAM']['ab_can'];
							$ram1=array('PO','PA','RU','RCRT'); // ruoli ammessi
							$ram2=array('DC','ND'); // ruoli ammessi se responsabili
							for ($y=0; $y < count($a['COD_FISC']); $y++) {
								if (in_array($a['KR'][$y],$ram1) or (in_array($a['KR'][$y],$ram2) and $a['FL_RESPONSABILE'][$y] == 1)){
									$bg='light';
									if ($a['KR'][$y] == 'PO' or $a['KR'][$y] == 'PA'){$bg='success';}
									if ($a['KR'][$y] == 'RU' or $a['KR'][$y] == 'RCRT'){$bg='primary';}
									if ($a['KR'][$y] == 'DC' or $a['KR'][$y] == 'ND' ){$bg='warning';}
									$ss.='<option class="alert-'.$bg.'" data-tokens="'.$a['COD_FISC'][$y].'" value="'.$a['COD_FISC'][$y].'"';
									if ($a['COD_FISC'][$y]==$v){$ss.=' selected';}
									$ss.='>'.$a['COGNOME'][$y].' '.$a['NOME'][$y].' ('.$a['COD_FISC'][$y].')</option>';
								}
							}
						}
						$s.='<select name="docint" id="docint" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep '.$d.'><option data-tokens="" value=""></option>'.$ss.'</select>';
					$s.='</div>';
				$s.='</div><br />';

				$s.=get_alert('<strong>Note</strong><br />esempio: la descrizione dell\' evento se GUEST (example: the description of the event if GUEST)','info text-center');
				$s.='<div class="row bg-light py-1">';
					$s.='<div class="col-sm-4 text-right">Note / Evento (Note / Event):</div>';
					$s.='<div class="col-sm-8">';
						if (!empty($j[0]['ra_note'])){$v=$j[0]['ra_note'];} else {$v='';}
						$s.='<textarea rows="5" name="note" id="note" class="form-control form-control-sm">'.$v.'</textarea>';
					$s.='</div>';
				$s.='</div><br />';
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_na - prima di campi SU");}
				if ($su or $ict){
					$ss='';
					$astati=array("bozza","richiesta","validata_ict","aspetta_ad","creato_ad","completata","storicizzata","rifiutata","forza_ldap");
					foreach($astati as $astati_i){
						$ss.='<option data-tokens="'.$astati_i.'" value="'.$astati_i.'"';
						if ($astati_i==$j[0]['ra_stato']){$ss.=' selected';}
						$ss.='>'.$astati_i.'</option>';
					}
					$s.='<div class="row bg-light py-1">';
						$s.='<div class="col-sm-4 text-right">Stato:</div>';
						$s.='<div class="col-sm-8">';
							$s.='<select  name="ra_stato" id="ra_stato" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep '.$d.'><option data-tokens="" value=""></option>'.$ss.'</select>';
						$s.='</div>';
					$s.='</div><br />';
				}

				if ($su){
					$s.=get_alert('<strong>Campi gestiti solo dal superuser</strong>','warning text-center');
					$asu=array('ra_k','ra_mail','ra_notificata','ra_dt_notifica','ra_d_afferenza','ra_inquadramento','ra_nuovo_inizio','ra_nc_referente','ra_motivo','ra_uid','ra_aliases','ra_redirect','ra_prima_password','ra_modifica_password','ra_uid_modifica_password','ra_cancellabile','ra_nfcm','ra_ldap_dn_ini','ra_ad_motivo','ra_ad_princ_mail','ra_ad_dn_ini','ra_usr_ins','ra_dt_ins','ra_ip_ins','ra_usr_mod','ra_dt_mod','ra_ip_mod');
					// $asu=array('ra_k','ra_mail','ra_notificata','ra_dt_notifica','ra_d_afferenza','ra_inquadramento','ra_nuovo_inizio','ra_nc_referente','ra_stato','ra_motivo','ra_uid','ra_aliases','ra_redirect','ra_prima_password','ra_modifica_password','ra_cancellabile','ra_nfcm','ra_ldap_dn_ini','ra_ad_motivo','ra_ad_princ_mail','ra_ad_dn_ini','ra_usr_ins','ra_dt_ins','ra_ip_ins','ra_usr_mod','ra_dt_mod','ra_ip_mod');
					// $asu=array('ra_k','ra_mail','ra_notificata','ra_dt_notifica','ra_inquadramento','ra_nuovo_inizio','ra_stato','ra_motivo','ra_uid','ra_aliases','ra_redirect','ra_prima_password','ra_modifica_password','ra_cancellabile','ra_nfcm','ra_ldap_dn_ini','ra_ad_motivo','ra_ad_princ_mail','ra_ad_dn_ini','ra_usr_ins','ra_dt_ins','ra_ip_ins','ra_usr_mod','ra_dt_mod','ra_ip_mod');
					// lista campi in sola lettura
					$asuro=array('ra_k','ra_usr_ins','ra_dt_ins','ra_ip_ins','ra_usr_mod','ra_dt_mod','ra_ip_mod');
					foreach ($asu as $asui){
						$s.='<div class="row bg-light py-1">';
							$s.='<div class="col-sm-4 text-right">'.$asui.':</div>';
							$s.='<div class="col-sm-8">';
								if (!empty($j[0][$asui])){$v=$j[0][$asui];} else {$v='';}
								$asuitf=true;
								$dto='';
								if (in_array($asui,array('ra_dt_notifica','ra_nuovo_inizio'))){$dto='dto';}
								if (in_array($asui,$asuro)){
									$s.='<strong>'.$v.'</strong>';
									$asuitf=false;
								}
								if ($asui == 'ra_stato'){
									$ss='';
									$astati=array("bozza","richiesta","validata_ict","aspetta_ad","creato_ad","completata","storicizzata","rifiutata");
									foreach($astati as $astati_i){
										$ss.='<option data-tokens="'.$astati_i.'" value="'.$astati_i.'"';
										if ($astati_i==$v){$ss.=' selected';}
										$ss.='>'.$astati_i.'</option>';
									}
									$s.='<select  name="'.$asui.'" id="'.$asui.'" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep '.$d.'><option data-tokens="" value=""></option>'.$ss.'</select>';
									$asuitf=false;
								}
								if ($asuitf){
									$s.='<input type="text" name="'.$asui.'" id="'.$asui.'" class="form-control form-control-sm" value="'.$v.'" '.$dto.' '.$d.' />';
								}
							$s.='</div>';
						$s.='</div>';
					}
				}
			$s.='</div>';
		$s.='</div>';

	$s.='</form>';
// if ($sviluppo_albo){write_log('_tmp.log',"get_ra_na - fine form");}
	return $s;
}
function get_e_ra(){							// completa le informazioni per l'account (calcolo uid)
	global $conn_new, $ldap_conn, $conn_sl;

	$nome='account';
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}	// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}	// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}	// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}	// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict

	$s='';
	$e='';
	$ss='';
	$w='';	// warning

	$uid='';
	$p='';
	$alias='';
	
	$j=array();
	if (!empty($_REQUEST['k'])){
		$sql="select * from storico_ldap.richieste_account where ra_k='".$_REQUEST['k']."'";

		try {
			$a=mysqli_query($conn_sl,$sql);						
			if (!empty(mysqli_error($conn_sl))){
				$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');
			}
		} catch (Exception $ee) {
			$e.=get_alert(($su?$sql.'<br />':'').'Exception: '.$ee->getMessage(),'danger');
		}

		if (empty($e)){
			if (mysqli_num_rows($a) > 0) {	// dati trovati
				foreach($a as $row){$j[]=$row;}
			}
		}
	}	

	// obbligatori per PF, GE e GU
	if (empty($j[0]['ra_cognome'])){$e.=get_alert('<strong>Cognome</strong> obbligatorio','danger');}
	if ($j[0]['ra_tipo']=='PF'){	// Persona fisica (account classico)
		if (empty($j[0]['ra_nome'])){$e.=get_alert('<strong>Nome</strong> obbligatorio','danger');}
		if (empty($j[0]['ra_cf'])){$e.=get_alert('<strong>Codice fiscale</strong> obbligatorio','danger');}
		if (empty($j[0]['ra_afferenza'])){$e.=get_alert('<strong>Struttura</strong> obbligatoria','danger');}
		if (empty($j[0]['ra_d_afferenza'])){$e.=get_alert('<strong>Descrizione Struttura</strong> obbligatoria','danger');}
		if (empty($j[0]['ra_ruolo'])){$e.=get_alert('<strong>Categoria</strong> obbligatoria','danger');}
		// non obbligatorio in quanto gli account possono essere creati a prescindere dalla mail (es. visiting)
		// if (empty($j[0]['ra_mail_notifica'])){$e.=get_alert('<strong>Mail esterna</strong> obbligatoria','danger');}
	}
	if ($j[0]['ra_tipo']=='GU'){	// guest account
		if (empty($j[0]['ra_cf'])){$e.=get_alert('<strong>Codice fiscale</strong> obbligatorio','danger');}
		if (empty($j[0]['ra_nome'])){$e.=get_alert('<strong>Nome</strong> obbligatorio','danger');}
		if (empty($j[0]['ra_afferenza'])){$e.=get_alert('<strong>Struttura</strong> obbligatoria','danger');}
		if (empty($j[0]['ra_ruolo'])){$e.=get_alert('<strong>Categoria</strong> obbligatoria','danger');}
		if (empty($j[0]['ra_note'])){$e.=get_alert('<strong>Insrisci la descrizione dell\'evento/attivit&agrave; nelle note</strong> obbligatorio','danger');}
		if (empty($j[0]['ra_cf_referente'])){$e.=get_alert('<strong>Codice fiscale del referente</strong> obbligatorio','danger');}
	}
	if ($j[0]['ra_tipo']=='GE'){	// account generico
		if (empty($j[0]['ra_cf_referente'])){$e.=get_alert('<strong>Codice fiscale del referente</strong> obbligatorio','danger');}
	}
	if ($e==''){
		if ($j[0]['ra_tipo']=='PF'){
			// calcola uid
			// The samAccountName length is fixed to 20 characters only and cannot contain any of these characters: “ / \ : ; | = , + * ? < > . 
			// The field will not accept when you try to type more than 20 characters in the user login name.
			// The value of samAccountName must be unique for all domain objects. 14 feb 2023
			$uidd='';
			$nome=strtolower(implode('',explode(' ',$j[0]['ra_nome']))); 		// minuscolo senza spazi
			$cognome=strtolower(implode('',explode(' ',$j[0]['ra_cognome'])));	// minuscolo senza spazi
			if (strlen($cognome) > 18){
				// la successiva for non funzionerebbe mai per eccessiva lunghezza del cognome
				// provo a togliere (partendo dal fondo) una o più parti del cognome
				$acg=explode(' ',strtolower($j[0]['ra_cognome']));
				if (strlen($acg[0]) > 18){
					$e=get_alert("Impossibile calcolare l'uid di ".$j[0]['ra_cognome'].' perch&eacute; la prima parte del cognome conta '.strlen($acg[0]).' caratteri mentre il massimo &egrave; 18','danger',true);
				} else {
					$cognome='';
					for ($i=0; $i < count($acg); $i++){
						if (strlen($cognome.$acg[$i]) > 18){break;}
						$cognome.=$acg[$i];
					}
				}
			}
		// }
	// }
	// if ($e==''){
		// if ($j[0]['ra_tipo']=='PF'){
			// $uid=substr($nome,0,1).'.'.$cognome;		// uid classico
			$uid=crt_filter(substr(ab_str_utf8_ascii($nome),0,1).'.'.ab_str_utf8_ascii($cognome),'§ma§mi§nu.-');	// uid classico
			$stato=get_stato($uid);
			if($stato['k']!=0){ 
				$w.=$stato['d'];						// warning per uid classico non corretto
				for ($i=1; $i < strlen($nome); $i++) {
					$pln=substr(ab_str_utf8_ascii($nome),0,$i+1); 			// prime lettere nome
					// $uid=$pln.'.'.$cognome;
					$uid=crt_filter($pln.'.'.ab_str_utf8_ascii($cognome),'§ma§mi§nu.');	
					if(strlen($uid) > 20){break;} 		//non posso aggiungere crt, ho superato i 20 crt
					$stato=get_stato($uid);
					if($stato['k']==0){break;}			// uid corretto
					$w.=$stato['d'];					// warning di un uid calcolato ma non corretto
				}
			}
			if($stato['k']!=0){ 
				// il primo giro non ha trovato un uid corretto 
				// provo ad inserire un numero in fondo al pezzo del nome
				if (strlen($cognome) <= 17){
					for ($i=0; $i < strlen($nome); $i++) {
						$pln=substr(ab_str_utf8_ascii($nome),0,$i+1); 	// prime lettere nome
						for ($i=1; $i < 10; $i++) {
							// $uid=$pln.$i.'.'.$cognome;
							$uid=crt_filter($pln.$i.'.'.ab_str_utf8_ascii($cognome),'§ma§mi§nu.');	
							if(strlen($uid) > 20){break;}
							$stato=get_stato($uid);
							if($stato['k']==0){break;}
							$w.=$stato['d'];
						}
					}
				}
			}
//			if($stato['k']!=0){ 
//				// anche il secondo giro non ha trovato un uid corretto
//				// provo ad inserire un numero in fondo all'uid base
//				if (strlen($uidd) <= 19){
//					for ($i=0; $i < 10; $i++) {
//						$uid=$uidd.$i;
//						$stato=get_stato($uid);
//						if($stato['k']==0){break;}
//						$w.=$stato['d'];
//					}
//				}
//			}
			if($stato['k']!=0){
				$e.=get_alert('<strong>Impossibile calcolare il corretto uid da assegnare','danger');
				$uid='';
				$alias='';
			} else {
				// calcola alias

				// 25 settembre 2023 
				// lunghezza massima dell'indirizzo sembra essere 79 crt (@santannapisa.it = 16 ... restano 63)
				// i caratteri non ammessi (sarebbero sostituiti con il carattere _ ) sono: (spazio) ` ( ) | = ? / %
				$anome=explode(' ',crt_filter(ab_str_utf8_ascii($j[0]['ra_nome']),'§ma§mi§nu. '));
				$acognome=explode(' ',crt_filter(ab_str_utf8_ascii($j[0]['ra_cognome']),'§ma§mi§nu. '));
				$nome1=$anome[0];
				$alias = $nome1.'.';
				foreach ($acognome as $cc){
					if (strlen($alias . $cc) < 63){
						$alias .= $cc;
					}
				}
				$stato=get_stato($alias);
				if($stato['k']!=0){ 			// warning per alias classico non corretto
					$w.=$stato['d'];
					for ($i=1; $i < 10; $i++) {
						// $alias=$nome1.$i.'.'.$cognome1;
						$alias=crt_filter(ab_str_utf8_ascii($nome1.$i.'.'.$cognome1),'§ma§mi§nu.');	
						$alias = $nome1.$i.'.';
						foreach ($acognome as $cc){
							if (strlen($alias . $cc) < 63){
								$alias .= $cc;
							}
						}
						$stato=get_stato($alias);
						if($stato['k']==0){break;}
						$w.=$stato['d'];
					}
				}
				if($stato['k']!=0){$e.=get_alert('<strong>Impossibile calcolare il corretto alias da assegnare','danger');}

// 				--- versione precedente al 25 settembre 2023
//				// costruisco solo con il primo nome
//				$nome1=array_values(explode(' ',$j[0]['ra_nome']))[0];
//				$cognome1=array_values(explode(' ',$j[0]['ra_cognome']))[0];
//				// $alias=$nome1.'.'.$cognome1;	// alias classico
//				$alias=crt_filter(ab_str_utf8_ascii($nome1.'.'.$cognome1),'§ma§mi§nu.');	
//				$stato=get_stato($alias);
//				if($stato['k']!=0){ 			// warning per alias classico non corretto
//					$w.=$stato['d'];
//					for ($i=1; $i < 10; $i++) {
//						// $alias=$nome1.$i.'.'.$cognome1;
//						$alias=crt_filter(ab_str_utf8_ascii($nome1.$i.'.'.$cognome1),'§ma§mi§nu.');	
//						$stato=get_stato($alias);
//						if($stato['k']==0){break;}
//						$w.=$stato['d'];
//					}
//				}
//				if($stato['k']!=0){$e.=get_alert('<strong>Impossibile calcolare il corretto alias da assegnare','danger');}
			}
		}
		if ($j[0]['ra_tipo']=='GU'){
			// trovo il primo libero gxxxxx partendo da g10000
			for ($i=10000; $i < 99999; $i++) {
				$uid='g'.$i;
				$stato=get_stato($uid);
				if($stato['k']==0){break;}
				// $w.=$stato['d'];
			}
		}
		if ($j[0]['ra_tipo']=='GE'){
			// calcola uid
			$uidd='';
			$cognome=strtolower(implode('',explode(' ',$j[0]['ra_cognome'])));	// minuscolo senza spazi
			if (strlen($cognome) > 18){
				// la successiva for non funzionerebbe mai per eccessiva lunghezza del cognome
				// provo a togliere (partendo dal fondo) una o più parti del cognome
				$acg=explode(' ',strtolower($j[0]['ra_cognome']));
				if (strlen($acg[0]) > 20){
					$e=get_alert("Impossibile calcolare l'uid di ".$j[0]['ra_cognome'].' perch&eacute; la prima parte conta '.strlen($acg[0]).' caratteri mentre il massimo &egrave; 20','danger',true);
				} else {
					$cognome='';
					for ($i=0; $i < count($acg); $i++){
						if (strlen($cognome.$acg[$i]) > 20){break;}
						$cognome.=$acg[$i];
					}
				}
			}

			// $uid=$cognome;
			$uid=crt_filter(ab_str_utf8_ascii($cognome),'§ma§mi§nu');	
			$stato=get_stato($uid);
			if($stato['k']!=0){ // il primo giro non ha trovato un uid corretto
				$w.=$stato['d'];
				for ($i=1; $i < 10; $i++) {
					// $uid=$cognome.$i;
					$uid=crt_filter(ab_str_utf8_ascii($cognome).$i,'§ma§mi§nu');	
					$stato=get_stato($uid);
					if($stato['k']==0){break;}
					$w.=$stato['d'];
				}
			}
			if($stato['k']!=0){$e.=get_alert('<strong>Impossibile calcolare il corretto uid da assegnare','danger');}
		}
	}
	
	// cerco su alumni
	// $j=array();
	$stato=get_stato($uid);
	if($stato['k']==0){
		if (!empty($_REQUEST['k'])){
			$sql="select * from storico_ldap.richieste_account_alumni where lower(raal_ldap_uid) = '".strtolower($uid)."' or lower(raal_ldap_uid) = '".strtolower($alias)."'";

			try {
				$a=mysqli_query($conn_sl,$sql);						
				if (!empty(mysqli_error($conn_sl))){
					$e.=get_alert(($su?$sql.'<br />':'').mysqli_error($conn_sl),'danger');
				}
			} catch (Exception $ee) {
				$e.=get_alert(($su?$sql.'<br />':'').'Exception: '.$ee->getMessage(),'danger');
			}

			if (empty($e)){
				if (mysqli_num_rows($a) > 0) {	// dati trovati
					foreach($a as $row){
						// $j[]=$row;
						$w.=get_alert($row->raal_userPrincipalMail.' esistente in Alumni','warning');
					}
				}
			}
		}	
	}	

	if($stato['k']!=0){
		$e.=get_alert('<strong>Impossibile calcolare il corretto uid da assegnare','danger');
		$uid='';
		$alias='';
	}
	
	if($e!=''){$ss.=$e;}
	if($w!=''){$ss.=$w;}

	$ss.='<div class="row p-3">';
		$ss.='<div class="col-sm-12 alert alert-dark">';
			$ss.='<div class="row">';
				$ss.='<div class="col-sm-2 text-right">Tipo:</div>';
				$ss.='<div class="col-sm-10">';
					$ss.='<input type="text" name="tipo" id="tipo" class="form-control form-control-sm" value='.$j[0]['ra_tipo'].' readonly  />';
				$ss.='</div>';
			$ss.='</div>';	

			// uid
			$ss.='<div class="row">';
				$ss.='<div class="col-sm-2 text-right">Uid:</div>';
				$ss.='<div class="col-sm-5">';
					$ss.='<input type="text" name="c_uid" id="c_uid" class="form-control form-control-sm" value="'.$uid.'" readonly />';
				$ss.='</div>';
				$ss.='<div class="col-sm-5">';
					$ss.='<input type="text" name="uid" id="uid" class="form-control form-control-sm" value="'.$uid.'" />';
				$ss.='</div>';
			$ss.='</div>';

			// password
			$p=crea_password();
			$ss.='<div class="row">';
				$ss.='<div class="col-sm-2 text-right">Password:</div>';
				$ss.='<div class="col-sm-5">';
					$ss.='<input type="text" name="c_password" id="c_password" class="form-control form-control-sm" value='.$p.' readonly  />';
				$ss.='</div>';
				$ss.='<div class="col-sm-5">';
					$ss.='<input type="text" name="password" id="password" class="form-control form-control-sm" value='.$p.' />';
				$ss.='</div>';
			$ss.='</div>';

			// alias
			$ss.='<div class="row">';
				$ss.='<div class="col-sm-2 text-right">Alias:</div>';
				$ss.='<div class="col-sm-5">';
					$ss.='<input type="text" name="c_alias" id="c_alias" class="form-control form-control-sm" value="'.$alias.'" readonly />';
				$ss.='</div>';
				$ss.='<div class="col-sm-5">';
					$ss.='<input type="text" name="alias" id="alias" class="form-control form-control-sm" value="'.$alias.'" />';
				$ss.='</div>';
			$ss.='</div>';
		$ss.='</div>';
	$ss.='</div>';	

	// $s.=get_alert(json_encode($_REQUEST));
	$tit='<strong>Completa informazioni Account</strong>';
	$s.=get_alert($tit,'info text-center');
	$s.='<div class="row">';
		$s.='<div class="col-sm-6">';
			$s.=get_ra_na(); // form dati account
		$s.='</div>';
		$s.='<div class="col-sm-6">';
			if ($e==''){
				$s.='<br /><button class="btn btn-success btn-block" act="get_ra" dom="mm" conferma="y" type="era" k="'.$_REQUEST['k'].'" tab="na" getf="uid,password,tipo,alias">Conferma</button><br />';
			}
			$s.=$ss;
		$s.='</div>';
	$s.='</div>';
	return $s;
}
function get_cerca_account(){ 		// tabella relativa a ricerca
	global $conn_new, $ldap_conn, $ad_conn;
	if (empty($_REQUEST['find_account'])){
		return get_alert('Inserisci una ricerca<br /><strong>Uid</strong> o <strong>Codice fiscale</strong> devono essere inseriti per intero<br /><strong>Nome</strong> o <strong>Cognome</strong> possono essere inseriti in modo parziale, non inserire entrambi - la ricerca non tiene conto delle maiuscole/minuscole','warning');
	}
	if (strpos($_REQUEST['find_account'],'*')){
		return get_alert('<strong>Non inserire</strong> il carattere <strong>*</strong> nella ricerca','danger');
	}
	$f=$_REQUEST['find_account'];
	$s='<br />';
	$b=array("ou=Users,o=sss,c=it","ou=GuestUsers,o=sss,c=it");
	for ($y=0; $y < count($b); $y++){	// loop di ricerca su Users e GuestUsers
		$info='';
		$base=$b[$y];
		$p="(|(uid=$f)(x-codicefiscale=$f)(sn=*$f*)(givenname=*$f*)(description=*$f*)(x-creatoreUid=$f))";
		$result=ldap_search($ldap_conn, $base, $p);
		if ($result!==false){
			$info = ldap_get_entries($ldap_conn, $result);
		}
		if ($info['count'] > 0){
			$tipo=(strpos($base,'Guest')?'guest':'ldap');
			$n='acc'.$tipo;
			$s.='<div class="row bg-light">';
				$s.='<div class="col-sm-2">';
					$s.=get_alert('<strong>'.strtoupper($tipo).'</strong>','dark py-1 text-center',true);
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-info btn-sm btn-block" act="toggleDTX" tabledom="tb-'.$n.'">Toggle DataTables</button>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
				$s.='</div>';
				$s.='<div id="alert-'.$n.'" class="col-sm-6"></div>';
			$s.='</div>';
			$s.='<table class="table table-striped table-sm table-responsive" id="tb-'.$n.'"><thead><tr><th></th><th>uid</th><th>nome e cognome</th><th>cf</th><th>ruolo</th><th>afferenza</th><th>data scadenza</th><th>creatore</th><th>telefono</th></tr></thead><tbody>';
			
			for ($i=0; $i < $info['count']; $i++){
				$aad='';$ldap_dn='';$ad_dn='';$guest_dn='';$dn='';
				$sn='';$givenname='';$cf='';$uid='';$creatore='';$telefono='';
				$kr='';$ka='';
				if (!empty($info[$i]['dn'])){
					$dn=$info[$i]['dn'];
					if (!strpos($base, 'GuestUsers')){
						$ldap_dn=$info[$i]['dn'];
					} else {
						$guest_dn=$info[$i]['dn'];
					}
				}
				if (!empty($info[$i]['sn'])){$sn=$info[$i]['sn'][0];}
				if (!empty($info[$i]['givenname'][0])){$givenname=$info[$i]['givenname'][0];}
				if (!empty($info[$i]['x-codicefiscale'][0])){$cf=$info[$i]['x-codicefiscale'][0];}
				if (!empty($info[$i]['uid'][0])){$uid=$info[$i]['uid'][0];}
				if (!empty($info[$i]['x-creatoreuid'][0])){$creatore=$info[$i]['x-creatoreuid'][0];}
				if (!empty($info[$i]['x-scadenzatempo'][0])){$scadenza=$info[$i]['x-scadenzatempo'][0];}
				if (!empty($info[$i]['businesscategory'][0])){$kr=$info[$i]['businesscategory'][0];}
				if (!empty($info[$i]['departmentnumber'][0])){$ka=$info[$i]['departmentnumber'][0];}
				if (!empty($info[$i]['telephonenumber'][0])){$telefono=$info[$i]['telephonenumber'][0];}
				if (!strpos($base, 'GuestUsers')){ // cerco anche AD
					$u=$info[$i]['uid'][0];
					// if (!empty($info[$i]['dn'][0])){$ldap_dn=$info[$i]['dn'][0];}
					$sr = ldap_search($ad_conn, 'ou=openldap,dc=sssapisa,dc=it', "(userprincipalname=*$u*)"); // cerco lo username
					if ($sr) {
						$en = ldap_get_entries($ad_conn, $sr);	// leggo i dati trovati
						$nen = ldap_count_entries($ad_conn, $sr);	// numero elementi trovati; dovrebbe essere solo 1 $en[0]
						if ($nen > 0){
							$aad=json_decode(safe_json_encode($en[0]), true);
							if (!empty($aad['dn'])){$ad_dn=$aad['dn'];}
						}
					}
				}
				$s.='<tr>';
					// bottoni,uid,nome e cognome,cf,ruolo,afferenza,data scadenza,creatore
					$s.='<td>';
						$s.='<button class="btn btn-warning btn-sm btn-block" act="mod-acc" cf="'.$cf.'" tipo="'.$tipo.'" dn="'.addslashes($dn).'" dom="la_bottom">Modifica</button>';
					$s.='</td>';
					$s.='<td>'.$uid.'</td>';
					$s.='<td>'.$givenname.' '.$sn.'</td>';
					$s.='<td>'.$cf.'</td>';
					$ss='';
					if($kr!=''){
						if (!empty($_SESSION['IAM']['lista_ruoli'])){
							for ($j=0; $j < count($_SESSION['IAM']['lista_ruoli']['K']); $j++) {
								if ($_SESSION['IAM']['lista_ruoli']['K'][$j]==$kr){$ss.=$_SESSION['IAM']['lista_ruoli']['D'][$j];}
							}
						}
					}
					$s.='<td>'.$ss.'</td>'; // ruolo
					$ss='';
					if($ka!=''){
						if (!empty($_SESSION['IAM']['lista_afferenze'])){
							for ($j=0; $j < count($_SESSION['IAM']['lista_afferenze']['K']); $j++) {
								if ($_SESSION['IAM']['lista_afferenze']['K'][$j]==$ka){$ss.=$_SESSION['IAM']['lista_afferenze']['D'][$j];}
							}
						}
					}
					$s.='<td>'.$ss.'</td>'; // afferenza
					// $ss=substr($scadenza,6,2).'/'.substr($scadenza,4,2).'/'.substr($scadenza,0,4);
					if (empty($scadenza)){
						$s.='<td></td>'; // scadenza
					} else {
						$ss=substr($scadenza,0,4).'/'.substr($scadenza,4,2).'/'.substr($scadenza,6,2);
						$s.='<td>'.$ss.'</td>'; // scadenza
					}
					$s.='<td>'.$creatore.'</td>';
					$s.='<td>'.$telefono.'</td>';
				$s.='</tr>';
			}
			$s.='</tbody></table>';
		}
	}
	// @@@@@@@@ ricerca su carriere future e su preimmatricolazioni per proporre l'inserimento
	
	if ($s=='<br />'){$s.=get_alert('Non trovato','danger');}
	return $s;
}	
function get_mod_account(){ 			// form di modifica del singolo account
	global $conn_new, $ldap_conn, $ad_conn;
	if (!empty($_REQUEST['dn'])){$dn=$_REQUEST['dn'];} else {$dn='';}
	if ($dn==''){return get_alert('Non trovato','warning');}
	$info='';
	$p="(uid=*)";
	$result=ldap_search($ldap_conn, $dn, $p);
	if ($result!==false){$info = ldap_get_entries($ldap_conn, $result);}
	if (empty($info['count'])){return get_alert('Non trovato','warning');}
	if ($info['count'] > 0){
		$i=0;
		$aad='';$ldap_dn='';$ad_dn='';
		if (!empty($info[$i]['dn'])){$ldap_dn=$info[$i]['dn'];}
		if (!strpos($dn, 'GuestUsers')){ // cerco anche AD xché non è un guest
			$u=$info[$i]['uid'][0];
			// if (!empty($info[$i]['dn'][0])){$ldap_dn=$info[$i]['dn'][0];}
			$sr = ldap_search($ad_conn, 'ou=openldap,dc=sssapisa,dc=it', "(userprincipalname=*$u*)"); // cerco lo username
			if ($sr) {
				$en = ldap_get_entries($ad_conn, $sr);	// leggo i dati trovati
				$nen = ldap_count_entries($ad_conn, $sr);	// numero elementi trovati; dovrebbe essere solo 1 $en[0]
				if ($nen > 0){
					$aad=json_decode(safe_json_encode($en[0]), true);
					if (!empty($aad['dn'])){$ad_dn=$aad['dn'];}
				}
			}
		}
		
		// $aad=get_ad_data($info[$i]['uid'][0]);

		$sx=implode('',explode('.',$info[$i]['uid'][0])); // uid senza il .
		$s='<form id="f_'.$sx.'" class="">';

			$s1='dark';
			if (!empty($info[$i]['x-scadenzatempo'][0])){
				if (substr($info[$i]['x-scadenzatempo'][0],0,8) > date("Ymd")){$s1='success';} else {$s1='danger';}
			}
			$s.='<div class="row alert alert-'.$s1.' py-1">';
				$s.='<div class="col-sm-8">';
					
					$s.=get_can_d($info[$i]['uid'][0]); // dettaglio dal gestionale

					$s.='<div class="row bg-secondary text-white">'; // intestazione
						$s.='<div class="col-sm-2 text-center"><strong></strong></div>';
						$s.='<div class="col-sm-5 text-center"><strong>LDAP</strong></div>';
						$s.='<div class="col-sm-5 text-center"><strong>AD</strong></div>';
					$s.='</div>';

					$s.='<div class="row bg-light py-2">';
						$s.='<div class="col-sm-2 text-right">Uid:</div>';
						$s.='<div class="col-sm-4">';
							$s.='<strong>'.$info[$i]['uid'][0].'</strong>';
						$s.='</div>';
						$s.='<div class="col-sm-1">';
							$s.='<strong>'.(strpos($dn, 'GuestUsers')?'<span class="alert-warning p-1">GUEST':'<span class="alert-success p-1">LDAP').'</span></strong>';
						$s.='</div>';
						$da=''; if (!empty($aad['mailnickname'])){
							// $da=implode(' ',$aad['mailnickname']);
							$da=$aad['mailnickname'][0];
						} else {
							$da='<span class="bg-danger d-block text-white">Non presente in AD</span>';
						}
						$s.='<div class="col-sm-5">'.$da.'</div>';
					$s.='</div>';

					$s.='<div class="row bg-light">';
						$s.='<div class="col-sm-2 text-right">Nome:</div>';
						$d=''; if (!empty($info[$i]['givenname'][0])){$d=htmlentities($info[$i]['givenname'][0]);}
						$s.='<div class="col-sm-5">';
							$s.='<input type="text" name="ldap_nome_'.$sx.'" id="ldap_nome_'.$sx.'" class="form-control form-control-sm" value="'.$d.'" disabled />';
						$s.='</div>';
						$da=''; if (!empty($aad['givenname'])){
							// $da=implode(' ',$aad['givenname']);
							$da=$aad['givenname'][0];
						} else {
							if ($ad_dn != ''){$da='<span class="bg-warning d-block text-white">Campo non presente in AD</span>';}
						}
						$s.='<div class="col-sm-5">'.$da.'</div>';
					$s.='</div>';

					$s.='<div class="row bg-light">';
						$s.='<div class="col-sm-2 text-right">Cognome:</div>';
						$d=''; if (!empty($info[$i]['sn'][0])){$d=htmlentities($info[$i]['sn'][0]);}
						$s.='<div class="col-sm-5">';
							$s.='<input type="text" name="ldap_cognome_'.$sx.'" id="ldap_cognome_'.$sx.'" class="form-control form-control-sm" value="'.$d.'" disabled />';
						$s.='</div>';
						$da=''; if (!empty($aad['sn'])){
							// $da=implode(' ',$aad['sn']);
							$da=$aad['sn'][0];
						} else {
							if ($ad_dn != ''){$da='<span class="bg-warning d-block text-white">Campo non presente in AD</span>';}
						}
						$s.='<div class="col-sm-5">'.$da.'</div>';
					$s.='</div>';
				
					$s.='<div class="row bg-light">';
						$s.='<div class="col-sm-2 text-right">Email:</div>';
						$d=''; if (!empty($info[$i]['mail'][0])){$d=$info[$i]['mail'][0];}
						$s.='<div class="col-sm-5">';
							$s.='<input type="text" name="ldap_email_'.$sx.'" id="ldap_email_'.$sx.'" class="form-control form-control-sm" value="'.$d.'" />';
						$s.='</div>';
						$da=''; if (!empty($aad['mail'])){
							// $da=implode(' ',$aad['mail']);
							$da=$aad['mail'][0];
						} else {
							if ($ad_dn != ''){$da='<span class="bg-warning d-block text-white">Campo non presente in AD</span>';}
						}
						$s.='<div class="col-sm-5">'.$da.'</div>';
					$s.='</div>';

					$s.='<div class="row bg-light">';
						$s.='<div class="col-sm-2 text-right">Codice fiscale:</div>';
						$s.='<div class="col-sm-5">';
							$d=''; if (!empty($info[$i]['x-codicefiscale'][0])){$d=$info[$i]['x-codicefiscale'][0];}
							$s.='<input type="text" name="ldap_cf_'.$sx.'" id="ldap_cf_'.$sx.'" class="form-control form-control-sm" value="'.$d.'" />';
						$s.='</div>';
						$da=''; if (!empty($aad['xcodicefiscale'])){
							// $da=implode(' ',$aad['xcodicefiscale']);
							$da=$aad['xcodicefiscale'][0];
						} else {
							if ($ad_dn != ''){$da='<span class="bg-warning d-block text-white">Campo non presente in AD</span>';}
						}
						$s.='<div class="col-sm-5">'.$da.'</div>';
					$s.='</div>';
				
					$s.='<div class="row bg-light">';
						$s.='<div class="col-sm-2 text-right">Afferenza:</div>';
						$s.='<div class="col-sm-5">';
							$ss='';
							if (!empty($_SESSION['IAM']['lista_afferenze'])){
								for ($j=0; $j < count($_SESSION['IAM']['lista_afferenze']['K']); $j++) {
									$ss.='<option data-tokens="'.$_SESSION['IAM']['lista_afferenze']['K'][$j].'" value="'.$_SESSION['IAM']['lista_afferenze']['K'][$j].'"';
									if(!empty($info[$i]['departmentnumber'][0])){if ($_SESSION['IAM']['lista_afferenze']['K'][$j]==$info[$i]['departmentnumber'][0]){$ss.=' selected';}}
									$ss.='>'.$_SESSION['IAM']['lista_afferenze']['D'][$j].'</option>';
								}
							}
							$s.='<select name="ldap_ka_'.$sx.'" id="ldap_ka_'.$sx.'" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep disabled>
								<option data-tokens="0" value="0"></option>
								'.$ss.'
							</select>';
						$s.='</div>';
						$da=''; if (!empty($aad['departmentnumber'])){
							// $da=implode(' ',$aad['departmentnumber']);
							$da=$aad['departmentnumber'][0];
						} else {
							if ($ad_dn != ''){$da='<span class="bg-warning d-block text-white">Campo non presente in AD</span>';}
						}
						$s.='<div class="col-sm-5">'.$da.'</div>';
					$s.='</div>';

					$s.='<div class="row bg-light">';
						$s.='<div class="col-sm-2 text-right">Ruolo:</div>';
						$s.='<div class="col-sm-5">';
							$ss='';
							if (!empty($_SESSION['IAM']['lista_ruoli'])){
								for ($j=0; $j < count($_SESSION['IAM']['lista_ruoli']['K']); $j++) {
									$ss.='<option data-tokens="'.$_SESSION['IAM']['lista_ruoli']['K'][$j].'" value="'.$_SESSION['IAM']['lista_ruoli']['K'][$j].'"';
									if(!empty($info[$i]['businesscategory'][0])){if ($_SESSION['IAM']['lista_ruoli']['K'][$j]==$info[$i]['businesscategory'][0]){$ss.=' selected';}}
									$ss.='>'.$_SESSION['IAM']['lista_ruoli']['D'][$j].'</option>';
								}
							}
							$s.='<select name="ldap_kr_'.$sx.'" id="ldap_kr_'.$sx.'" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep disabled>
								<option data-tokens="0" value="0"></option>
								'.$ss.'
							</select>';
						$s.='</div>';
						$da=''; if (!empty($aad['businesscategory'])){
							// $da=implode(' ',$aad['businesscategory']);
							$da=$aad['businesscategory'][0];
						} else {
							if ($ad_dn != ''){$da='<span class="bg-warning d-block text-white">Campo non presente in AD</span>';}
						}
						$s.='<div class="col-sm-5">'.$da.'</div>';
					$s.='</div>';

					// @@@@@@@@@@@@@@@@@
					$s.='<div class="row bg-light">';
						$s.='<div class="col-sm-2 text-right">Data fine:</div>';
						$d=''; $d8='';
						if (!empty($info[$i]['x-scadenzatempo'][0])){
							$d8=substr($info[$i]['x-scadenzatempo'][0],0,8);
							$d=substr($d8,6,2).'/'.substr($d8,4,2).'/'.substr($d8,0,4);
						}
						$s.='<div class="col-sm-5">';
							$s.='<input type="text" name="ldap_df_'.$sx.'" id="ldap_df_'.$sx.'" class="form-control form-control-sm" value="'.$d.'" dto';
							if ($_SESSION['IAM']['uid_login'] != 'a.bongiorni'){$s.=' disabled';}
							$s.=' />';
							if (!empty($info[$i]['x-scadenzatempo'][0])){
								// calcolo il campo per accountexpires
								$ae = date_create_from_format("d/m/Y", $d);
								$sec = strtotime(substr($d8,0,4).'-'.substr($d8,4,2).'-'.substr($d8,6,2));
								$ae = (((float)$sec + 11644473600) * 10000000);
								$s.='<strong title="calcolata da LDAP">accountexpires</strong>: '.$ae;
							}
						$s.='</div>';
						$da=''; if (!empty($aad['xscadenzatempo'])){
							$da.=$aad['xscadenzatempo'][0].' -> ';
							$d=substr($aad['xscadenzatempo'][0],0,8);
							$d=substr($d,6,2).'/'.substr($d,4,2).'/'.substr($d,0,4);
							$da.=$d;
							// $da=implode(' ',$aad['xscadenzatempo']);
						} 
						if (!empty($aad['accountexpires'])){
							// list($usec, $sec) = explode(" ", microtime());
							// $d = (((float)$usec + (float)$sec + 11644473600) * 10000000);
							// $winInterval = round($d / 10000000);
							// $unixTimestamp = ($winInterval - 11644473600);
							// $s.=get_alert('AD - Calcolo di accountexpires ad ora <strong>'.$d.'</strong> equivalente a '.date("d/m/Y h:i:s", $unixTimestamp),'warning');
							$da.='<br><strong>accountexpires</strong>: '.$aad['accountexpires'][0].' -> ';
							// $da.=implode(' ',$aad['accountexpires'][0]);
							$d=$aad['accountexpires'][0];
							$winInterval = round($d / 10000000);
							$unixTimestamp = ($winInterval - 11644473600);
							$da.=''.date("d/m/Y h:i:s", $unixTimestamp).'';
						}
						$s.='<div class="col-sm-5">'.$da.'</div>';
					$s.='</div>';

					$s.='<div class="row bg-light">';
						$s.='<div class="col-sm-2 text-right">Samba:</div>';
						$s.='<div class="col-sm-5">';
							$ss='';
							$ss.='<option data-tokens="[UX]" value="[UX]"';
							if(!empty($info[$i]['sambaacctflags'][0])){if ('[UX]'==$info[$i]['sambaacctflags'][0]){$ss.=' selected';}}
							$ss.='>[UX] (Attivo)</option>';
							$ss.='<option data-tokens="[D]" value="[D]"';
							if(!empty($info[$i]['sambaacctflags'][0])){if ('[D]'==$info[$i]['sambaacctflags'][0]){$ss.=' selected';}}
							$ss.='>[D] (Disattivo)</option>';
							$s.='<select name="ldap_samba_'.$sx.'" id="ldap_samba_'.$sx.'" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep disabled>
								<option data-tokens="0" value="0"></option>
								'.$ss.'
							</select>';
						$s.='</div>';
						$s.='<div class="col-sm-5">';
							if ($ad_dn != ''){$s.='<span class="bg-danger d-block text-white">Campo non previsto in AD</span>';}
						$s.='</div>';
					$s.='</div>';

					$s.='<div class="row bg-light">';
						$s.='<div class="col-sm-2 text-right">Telefono:</div>';
						$s.='<div class="col-sm-5">';
							$d=''; if (!empty($info[$i]['telephonenumber'][0])){$d=$info[$i]['telephonenumber'][0];}
							$s.='<input type="text" name="ldap_telefono_'.$sx.'" id="ldap_telefono_'.$sx.'" class="form-control form-control-sm" value="'.$d.'" />';
						$s.='</div>';
						$da=''; if (!empty($aad['telephonenumber'])){
							// $da=implode(' ',$aad['telephonenumber']);
							$da=$aad['telephonenumber'][0];
						} else {
							if ($ad_dn != ''){$da='<span class="bg-warning d-block text-white">Campo non presente in AD</span>';}
						}
						$s.='<div class="col-sm-5">'.$da.'</div>';
					$s.='</div>';

					$s.='<div class="row bg-light">';
						$s.='<div class="col-sm-2 text-right">Cellulare:</div>';
						$s.='<div class="col-sm-5">';
							$d=''; if (!empty($info[$i]['mobile'][0])){$d=$info[$i]['mobile'][0];}
							$s.='<input type="text" name="ldap_cellulare_'.$sx.'" id="ldap_cellulare_'.$sx.'" class="form-control form-control-sm" value="'.$d.'" />';
						$s.='</div>';
						$da=''; if (!empty($aad['mobile'])){
							// $da=implode(' ',$aad['mobile']);
							$da=$aad['mobile'][0];
						} else {
							if ($ad_dn != ''){$da='<span class="bg-warning d-block text-white">Campo non presente in AD</span>';}
						}
						$s.='<div class="col-sm-5">'.$da.'</div>';
					$s.='</div>';

					$s.='<div class="row bg-light">';
						$s.='<div class="col-sm-2 text-right">x-creatoreUid:</div>';
						$s.='<div class="col-sm-5">';
							$d=''; if (!empty($info[$i]['x-creatoreuid'][0])){$d=$info[$i]['x-creatoreuid'][0];}
							$s.='<input type="text" name="ldap_creatoreUid_'.$sx.'" id="ldap_creatoreUid_'.$sx.'" class="form-control form-control-sm" value="'.$d.'" disabled />';
						$s.='</div>';
						if (!empty($aad['x-creatoreuid'])){
							// $da=implode(' ',$aad['x-creatoreuid']);
							$da=$aad['x-creatoreuid'][0];
						} else {
							if ($ad_dn != ''){$da='<span class="bg-danger d-block text-white">Campo non previsto in AD</span>';}
						}
						$s.='<div class="col-sm-5">'.$da.'</div>';
					$s.='</div>';

					$s.='<div class="row bg-light">';
						$s.='<div class="col-sm-2 text-right">description:</div>';
						$s.='<div class="col-sm-5">';
							if (!empty($info[$i]['description'][0])){$d=$info[$i]['description'][0];}
							$s.='<input type="text" name="ldap_description_'.$sx.'" id="ldap_description_'.$sx.'" class="form-control form-control-sm" value="'.$d.'" disabled />';
						$s.='</div>';
						$da=''; if (!empty($aad['description'])){
							// $da=implode(' ',$aad['description']);
							$da=$aad['description'][0];
						} else {
							if ($ad_dn != ''){$da='<span class="bg-warning d-block text-white">Campo non presente in AD</span>';}
						}
						$s.='<div class="col-sm-5">'.$da.'</div>';
					$s.='</div>';

				$s.='</div>';

				$s.='<div class="col-sm-4">';
					$s.='<div class="container-fluid">';
						$s.='<div class="row">';
							$s.='<div class="col-sm-6">';
								$s.='<button class="btn btn-success btn-sm btn-block" act="sm_acc" dom="out_'.$sx.'" uid="'.$info[$i]['uid'][0].'" tab="'.$sx.'" origine="'.(strpos($dn, 'GuestUsers')?'GUEST':'LDAP').'" ldap_dn="'.$ldap_dn.'" ad_dn="'.$ad_dn.'">Salva</button>';
							$s.='</div>';
							$s.='<div class="col-sm-6">';
								$s.='<button class="btn btn-danger btn-sm btn-block" act="dma" dom="out_'.$sx.'" uid="'.$info[$i]['uid'][0].'" tab="'.$sx.'" origine="'.(strpos($dn, 'GuestUsers')?'GUEST':'LDAP').'" ldap_dn="'.$ldap_dn.'" ad_dn="'.$ad_dn.'">Disabilita</button>';
							$s.='</div>';
						$s.='</div>';
						$s.='<div class="row"><div class="col-sm-12" id="out_'.$sx.'"></div></div>';
						$s.='<div class="row"><div class="col-sm-12" id="out_'.$sx.'">';
						$s.='<hr><strong>'.(strpos($dn, 'GuestUsers')?'GUEST':'LDAP').'</strong>';
						// $s.='<br />'.implode(', ',explode(',',json_encode($info)));
						$s.='<br />'.$ldap_dn;	

						if (!strpos($dn, 'GuestUsers')){ // 
							$s.='<hr><strong>AD</strong>';
							// $s.='<br />'.implode(', ',explode(',',json_encode($aad)));
							$s.='<br />'.$ad_dn;
						}
						$s.='</div></div>';
					$s.='</div>';
				$s.='</div>';
			$s.='</div>';
		$s.='</form>';
	}

	if ($s=='<br />'){$s.=get_alert('Non trovato','danger');}
	return $s;	
}																				
function get_modifica_account(){ 	// pagina dei form di modifica relativi alla ricerca
	global $conn_new, $ldap_conn, $ad_conn;
	if (empty($_REQUEST['find_account'])){
		return get_alert('Inserisci una ricerca<br /><strong>Uid</strong> o <strong>Codice fiscale</strong> devono essere inseriti per intero<br /><strong>Nome</strong> o <strong>Cognome</strong> possono essere inseriti in modo parziale, non inserire entrambi - la ricerca non tiene conto delle maiuscole/minuscole','warning');
	}
	if (strpos($_REQUEST['find_account'],'*')){
		return get_alert('<strong>Non inserire</strong> il carattere <strong>*</strong> nella ricerca','danger');
	}
	$f=$_REQUEST['find_account'];
	$s='';
	$b=array("ou=Users,o=sss,c=it","ou=GuestUsers,o=sss,c=it");
	for ($y=0; $y < count($b); $y++){	// loop di ricerca su Users e GuestUsers
		$info='';
		$base=$b[$y];
		$p="(|(uid=$f)(x-codicefiscale=$f)(sn=*$f*)(givenname=*$f*)(description=*$f*)(x-creatoreUid=$f))";
		$result=ldap_search($ldap_conn, $base, $p);
		if ($result!==false){
			$info = ldap_get_entries($ldap_conn, $result);
		}
		if ($info['count'] > 0){
			$s.='<div class="row alert alert-primary">';
				$s.='<div class="col-sm-8">';
					$s.='<h4>Numero risultati della ricerca: <strong class="text-danger">'.$info['count'].'</strong> in <strong class="text-danger">'.(strpos($base, 'GuestUsers')?'GUEST':'LDAP').'</strong></h4>';
				$s.='</div>';
			$s.='</div>';
					
			for ($i=0; $i < $info['count']; $i++){
				if (!empty($info[$i]['dn'])){
					$_REQUEST['dn']=$info[$i]['dn'];
					$s.=get_mod_account();
					// $_SESSION['IAM']['percento']=round((($i+1)/$info['count']*100),0);
					// if (!empty(ob_get_level())){flush(); ob_flush();}
				}
			}
		}
	}
	if ($s==''){$s.=get_alert('Non trovato','danger');}
	return '<br />'.$s;	
}
function get_stato($uid){ 				// elaborazione
	// esempio uid = a.bongiorni
	global $ldap_conn, $ad_conn, $conn_sl, $conn_new;
/*	
	controlli di esistenza
	-----------------------------------------------------------------
	1. esiste uid o cf in LDAP 						(1) BLOCCA
	2. esiste uid o cf in LDAP GUEST 				(1) BLOCCA
	3. esiste uid o cf in AD						(1) BLOCCA
	4. esiste uid in storico_ldap (anche alias)		(1) BLOCCA
	
	5. esiste uid in communigate (script perl)		(2) WARNING
	6. esiste uid o cf in ab_can (oracle) 			(2) WARNING
	
	se BLOCCA ricalcola aggiungendo una lettera del nome fino a tutta la lunghezza del nome
	se ancora BLOCCA aggiunge un mumero (1-9) al nome iniziado da una lettera 
*/	
	$k=0;
	$d='';
	$a=array();	// array dei risultati del controllo
	$a['UID_K']=0;									$a['UID_D']="";
	$a['LDAP_K']=0;									$a['LDAP_D']="";
	$a['LDAP_GUEST_K']=0;						$a['LDAP_GUEST_D']="";
	$a['AD_K']=0;										$a['AD_D']="";
	$a['LDAP_STORICO_K']=0;					$a['LDAP_STORICO_D']="";
	$a['LDAP_STORICO_ALIAS_K']=0;		$a['LDAP_STORICO_ALIAS_D']="";
	$a['LDAP_STORICO_ALUMNI_K']=0;	$a['LDAP_STORICO_ALUMNI_D']="";
	$a['CAN_K']=0;									$a['CAN_D']="";

	$ll=4;
	$rr=8;

	// UID (controllo formale sull'uid da controllare)
	if ($uid != crt_filter(ab_str_utf8_ascii($uid),'§ma§mi§nu.-')){		// solo miuscole, minuscole, numeri e i crt (.-)
		$a['UID_K']=1;									
		$a['UID_D']=get_alert("uid non corretto: ".$uid);
	}

	if ($a['UID_K']==0){

		// LDAP
		// $ldap_conn = ldap_connect($ip_ldap,389);
	// error_reporting(0);	
		if ($ldap_conn) {
			$dn="ou=Users,o=sss,c=it";
			// $p='(|(uid=a.bongiorni)(uid=a.signorini))';
			try {
				$result=ldap_search($ldap_conn, $dn, 'uid='.$uid);
			} catch (Exception $e) {
				// $o['err'].=get_alert('ERROR Message: ' .$e->getMessage(),'danger',true);
			}
			if ($result!==false){
				$info = ldap_get_entries($ldap_conn, $result);
				if ( $info and $info['count'] === 1 ) {
					$a['LDAP_K']=1;
					$alert='<div class="row"><div class="col-sm-'.$ll.'"><button class="btn btn-warning btn-sm btn-block" title="esiste in LDAP" dom="mm" act="dett_ldap" k="" uid="'.$uid.'">'.$uid.'</button></div><div class="col-sm-'.$rr.'">esiste in LDAP</div></div>';
					$a['LDAP_D']=get_alert($alert,'warning',true);
				}
			}

			$dn_guest="ou=guestusers,o=sss,c=it";
			// $p='(|(uid=a.bongiorni)(uid=a.signorini))';
			try {
				$result=ldap_search($ldap_conn, $dn_guest, 'uid='.$uid);
			} catch (Exception $e) {
				// $o['err'].=get_alert('ERROR Message: ' .$e->getMessage(),'danger',true);
			}
			if ($result!==false){
				$info = ldap_get_entries($ldap_conn, $result);
				if ( $info and $info['count'] === 1 ) {
					$a['LDAP_GUEST_K']=1;
					$alert='<div class="row"><div class="col-sm-'.$ll.'"><button class="btn btn-warning btn-sm btn-block" title="esiste in LDAP GUEST" dom="mm" act="dett_ldap" k="" uid="'.$uid.'">'.$uid.'</button></div><div class="col-sm-'.$rr.'">esiste in LDAP GUEST</div></div>';
					$a['LDAP_GUEST_D']=get_alert($alert,'warning',true);
				}
			}
		}
		// AD
		$adBind = ldap_bind($ad_conn,'sssapisa.it/OpenLdap/Servizi ICT/User Read','readuser');	// administrator/aDuNo2016tt
		$e=get_ldap_error('AD','ldap_bind<br>');if ($e!=''){$s.=$e;}
		// userprincipalname=a.bongiorni@santannapisa.it
		$sr = ldap_search($ad_conn, 'OU=OpenLdap,DC=sssapisa,DC=it', "(userprincipalname=$uid@santannapisa.it)");
		$a['AD_SR_K']=0;
		$a['AD_SR_D']=disp_var($sr,'sr',$ad_conn);
		
		if ($sr) {
			try {
				$en = ldap_get_entries($ad_conn, $sr);
				if ($en) {
					$a['AD_EN_K']=0;
					$a['AD_EN_D']=disp_var($en,'en',$ad_conn);
				}
				$nen = ldap_count_entries($ad_conn, $sr);
				// disp_var($nen,'nen',$ad_conn);
			} catch (Exception $e) {
				// if ($devel) {echo '<hr />Message: ' .$e->getMessage();};
			}
			if ($en) {
				if (!empty($en['count'])){
					if ($en['count'] > 0){
						$a['AD_K']=1;
						$alert='<div class="row"><div class="col-sm-'.$ll.'"><button class="btn btn-warning btn-sm btn-block" title="esiste in AD" dom="mm" act="dett_ad" k="" uid="'.$uid.'">'.$uid.'</button></div><div class="col-sm-'.$rr.'">esiste in AD</div></div>';
						$a['AD_D']=get_alert($alert,'warning',true);
					}
				}
			}
		}
	// error_reporting(E_ALL);

		// storico LDAP
		$j=array();
		$sql="select * from storico_ldap.richieste_account where lower(ra_uid)='".strtolower($uid)."'";
		// if ($su) {$s.=get_alert($sql);}
		
		$aa=mysqli_query($conn_sl,$sql);
		if (!empty(mysqli_error($conn_sl))){$e.=get_alert(mysqli_error($conn_sl),'danger');}
		if (mysqli_num_rows($aa) > 0) {	// dati trovati
		
			foreach($aa as $row){
				$j[]=$row;
			}
		}
		if (count($j)>0){
			$a['LDAP_STORICO_K']=1;
			$alert='<div class="row"><div class="col-sm-'.$ll.'"><button class="btn btn-warning btn-sm btn-block" title="esiste in LDAP STORICO" dom="mm" act="dett_storico" k="'.$j[0]['ra_k'].'" uid="'.$uid.'">'.$uid.'</button></div><div class="col-sm-'.$rr.'">esiste in LDAP STORICO</div></div>';
			$a['LDAP_STORICO_D']=get_alert($alert,'warning',true);
		}

		// storico ALIAS
		$j=array();
		$sql="select * from storico_ldap.richieste_account_alias where lower(raa_uid)='".strtolower($uid)."'";
		// if ($su) {$s.=get_alert($sql);}
		$aa=mysqli_query($conn_sl,$sql);
		if (!empty(mysqli_error($conn_sl))){$e.=get_alert(mysqli_error($conn_sl),'danger');}
		if (mysqli_num_rows($aa) > 0) {	// dati trovati
			foreach($aa as $row){
				$j[]=$row;
			}
		}
		if (count($j)>0){
			$a['LDAP_STORICO_ALIAS_K']=1;
			$alert='<div class="row"><div class="col-sm-'.$ll.'"><button class="btn btn-warning btn-sm btn-block" title="esiste in LDAP STORICO ALIAS" dom="mm" act="dett_storico" k="'.$j[0]['raa_ra_k'].'" uid="'.$uid.'">'.$uid.'</button></div><div class="col-sm-'.$rr.'">esiste in LDAP STORICO ALIAS</div></div>';
			$a['LDAP_STORICO_ALIAS_D']=get_alert($alert,'warning',true);
		}

		// storico ALUMNI
		$j=array();
		$sql="select * from storico_ldap.richieste_account_alumni where lower(raal_ldap_uid)='".strtolower($uid)."'";
		// if ($su) {$s.=get_alert($sql);}
		$aa=mysqli_query($conn_sl,$sql);
		if (!empty(mysqli_error($conn_sl))){$e.=get_alert(mysqli_error($conn_sl),'danger');}
		if (mysqli_num_rows($aa) > 0) {	// dati trovati
			foreach($aa as $row){
				$j[]=$row;
			}
		}
		if (count($j)>0){
			$a['LDAP_STORICO_ALUMNI_K']=1;
			$alert='<div class="row"><div class="col-sm-'.$ll.'"><button class="btn btn-warning btn-sm btn-block" title="esiste in LDAP STORICO ALUMNI" dom="mm" act="dett_storico" k="'.$j[0]['raal_k'].'" uid="'.$uid.'">'.$uid.'</button></div><div class="col-sm-'.$rr.'">esiste in LDAP STORICO ALUMNI</div></div>';
			$a['LDAP_STORICO_ALUMNI_D']=get_alert($alert,'warning',true);
		}

		// ab_can
		$i=array_search($uid,$_SESSION['IAM']['ab_can']['LDAP_UID']);
		if ($i !== false) {	// trovato
			$a['CAN_K']=1;
			$alert='<div class="row"><div class="col-sm-'.$ll.'"><button class="btn btn-warning btn-sm btn-block" title="esiste in AB_CAN" dom="mm" act="dett_can" k="" uid="'.$uid.'">'.$uid.'</button></div><div class="col-sm-'.$rr.'">esiste in AB_CAN</div></div>';
			$a['CAN_D']=get_alert($alert,'warning',true);
		}

	}

	// ----------------------------------------
	if (
		$a['UID_K']>0 
		or $a['LDAP_K']>0 
		or $a['LDAP_GUEST_K']>0 
		or $a['AD_K']>0 
		or $a['LDAP_STORICO_K']>0 
		or $a['LDAP_STORICO_ALIAS_K']>0
		or $a['LDAP_STORICO_ALUMNI_K']>0
		or $a['CAN_K']>0
	) {
		$k=1;
	}

	$d=$a['UID_D'].$a['LDAP_D'].$a['LDAP_GUEST_D'].$a['AD_D'].$a['LDAP_STORICO_D'].$a['LDAP_STORICO_ALIAS_D'].$a['LDAP_STORICO_ALUMNI_D'].$a['CAN_D'];
	$stato=array('k'=>$k,'d'=>$d,'a'=>$a);
	return $stato;
}
/*
function z_get_prepara_nuovo_account(){ // @@@ OBSOLETO - elaborazione preparazione dati per il nuovo account

	$s='<br />';
	$e='';
	if (empty($_REQUEST['nome'])){$e.=get_alert('<strong>Nome</strong> obbligatorio','danger');}
	if (empty($_REQUEST['cognome'])){$e.=get_alert('<strong>Cognome</strong> obbligatorio','danger');}
	if (empty($_REQUEST['email_ex'])){$e.=get_alert('<strong>Mail esterna</strong> obbligatoria','danger');}
	if (empty($_REQUEST['cf'])){$e.=get_alert('<strong>Codice fiscale</strong> obbligatorio','danger');}
	if (empty($_REQUEST['ka'])){$e.=get_alert('<strong>Struttura</strong> obbligatoria','danger');}
	if (empty($_REQUEST['kr'])){$e.=get_alert('<strong>Categoria</strong> obbligatoria','danger');}
	if ($e!=''){
		$s.=$e;
		return $e;
	}

	if ($_REQUEST['snac']=='PF'){
		// calcola uid
		$w='';
		$uidd='';
		$nome=strtolower(implode('',explode(' ',$_REQUEST['nome']))); 				// minuscolo senza spazi
		$cognome=strtolower(implode('',explode(' ',$_REQUEST['cognome'])));		// minuscolo senza spazi
		for ($i=0; $i < strlen($nome); $i++) {
			$pln=substr($nome,0,$i+1);	// prime lettere del nome
			$uid=$pln.'.'.$cognome;			// uid da verificare
			if ($i==0){$uidd=$uid;}			// salvo l'uid iniziale per il loop successivo con i numeri
			$stato=get_stato($uid);			// $stato = array con k=(0=ok,1=ko), d=descrizione motivo scarto, a=array dei controlli
			if($stato['k']==0){break;}
			$w.=$stato['d'];						// aggiunge un warning
		}
		if($stato['k']!=0){						// ancora non ho trovato una soluzione valida tento con i numeri
			$pln=substr($nome,0,1);			// prima lettera del nome
			for ($i=0; $i < 10; $i++) {	// tento un massimo di 10 numeri aggiunti alla prima lettera del nome
				// $uid=$uidd.$i;
				$uid=$uid=$pln.$i.'.'.$cognome;
				$stato=get_stato($uid);
				if($stato['k']==0){break;}
				$w.=$stato['d'];
			}
		}
		if($w!=''){$s.=get_alert($w,'warning');}
		if($stato['k']!=0){$e.=get_alert('<strong>Impossibile calcolare un uid da assegnare','danger');}
	}
	if ($_REQUEST['snac']=='GU'){
		// trovo il primo libero gxxxxx partendo da g10000
		for ($i=10000; $i < 99999; $i++) {
			$uid='g'.$i;
			$stato=get_stato($uid);
			if($stato['k']==0){break;}
			// $w.=$stato['d'];	// potrebbero essere molti
		}
		if($stato['k']!=0){$e.=get_alert('<strong>Impossibile calcolare un uid da assegnare','danger');}
	}

	if ($e!=''){
		$s.=$e;
		return $e;
	}
	
	$s.='<div class="row">';
		$s.='<div class="col-sm-3 text-right">Uid:</div>';
		$s.='<div class="col-sm-9">';
			$s.='<input type="text" name="uid" id="uid" class="form-control form-control-sm" readonly value='.$uid.' />';
		$s.='</div>';
	$s.='</div>';

	$p=crea_password();
	$s.='<div class="row">';
		$s.='<div class="col-sm-3 text-right">Password:</div>';
		$s.='<div class="col-sm-9">';
			$s.='<input type="text" name="password" id="password" class="form-control form-control-sm" value='.$p.' readonly  />';
		$s.='</div>';
	$s.='</div>';

	$s.='<div class="row">';
		$s.='<div class="col-sm-3 text-right">Tipo:</div>';
		$s.='<div class="col-sm-9">';
			$s.='<input type="text" name="tipo" id="tipo" class="form-control form-control-sm" value='.$_REQUEST['tipo'].' readonly  />';
		$s.='</div>';
	$s.='</div>';

	return $s;
}
function z_get_salva_nuovo_account(){ 	// @@@ OBSOLETO - elaborazione
	$s='<br />';
	$e=''; $w='';
	if (empty($_REQUEST['uid'])){
		$e.='<div class="row"><div class="col-sm-12 alert alert-danger"><strong>Uid</strong> non calcolato</div></div>';
	}
	if (empty($_REQUEST['password'])){
		$e.='<div class="row"><div class="col-sm-12 alert alert-danger"><strong>Password</strong> non calcolata</div></div>';
	}
	if ($e!=''){
		$s.=$e;
		return $s;
	}
	$s.=$w;

	$s.=get_alert('<strong>FUNZIONE IN SVILUPPO - non registra!</strong>','danger text-center');

	$s.=get_alert('CHECK esistenza','success');
	if ($_REQUEST['snac']=='PF'){
		$s.=get_alert('LDAP creato','success');
		$s.=get_alert('COMMUNIGATE','warning');
		$s.=get_alert('AD creato','success');
		$s.=get_alert('AZURE creato','info');
	}
	if ($_REQUEST['snac']=='GU'){
		$s.=get_alert('LDAP GUEST creato','success');
	}
	if ($_REQUEST['snac']=='PF'){
		$s.=get_alert('Mail a nuovo utente inviata','success');
	}
	$s.=get_alert('Mail a ICT inviata','success');
	return $s;
}
*/
function get_salva_modifica_account(){ 	// elaborazione
	global $ldap_conn, $ad_conn, $conn_new;
	$sx='';
	$s='<br />';
	$e=''; $w='';
	if (empty($_REQUEST['uid'])){
		$e.='<div class="row"><div class="col-sm-12 alert alert-danger"><strong>Uid</strong> sconosciuto</div></div>';
	} else {
		$sx=implode('',explode('.',$_REQUEST['uid']));
	}
	if (empty($_REQUEST['ldap_cf_'.$sx])){
		$w.='<div class="row"><div class="col-sm-12 alert alert-warning"><strong>Codice Fiscale</strong> errato o mancante</div></div>';
	}
	// @@@@@@@@@@ effettuare una verifica per cui telephonenumber e mobile non siano già utilizzati da un altro account

	if ($e!=''){
		$s.=$e;
	} else {
		$s.=$w;

		$s.='<div class="row"><div class="col-sm-12 alert alert-warning"><strong>
		FUNZIONE IN SVILUPPO (VERIFICARE)
		<br>non registra i campi vuoti
		<br>non registra GUEST
		<br>aggiorna LDAP e AD, AB_CAN e ANAGRAFICHE_DA_LDAP
		</strong></div></div>';

// 	givenname					ldap_nome_abongiorni				=Alberto
// 	sn								ldap_cognome_abongiorni			=Bongiorni
// 	mail							ldap_email_abongiorni				=alberto.bongiorni%40santannapisa.it
// 	x-codicefiscale		ldap_cf_abongiorni					=BNGLRT57T20G702B
//  xcodicefiscale
// 	departmentnumber	ldap_ka_abongiorni					=005730
// 	businesscategory	ldap_kr_abongiorni					=ND
// 	x-scadenzatempo		ldap_df_abongiorni					=02%2F02%2F2222
//  xscadenzatempodata
//	accountexpiresdata
// 	sambaacctflags		ldap_samba_abongiorni				=%5BUX%5D
// 	telephonenumber		ldap_telefono_abongiorni		=3322
// 	mobile						ldap_cellulare_abongiorni		=
// 	x-creatoreuid			ldap_creatoreUid_abongiorni	=
// 	description				ldap_description_abongiorni	=Alberto+Bongiorni+aaa

// func=sm_acc
// obj_val=
// class=btn+btn-success+btn-sm+btn-block
// act=sm_acc
// dom=out_abongiorni
// uid=a.bongiorni
// tab=abongiorni
// origine=LDAP
// ldap_dn=uid%3Da.bongiorni%2Cou%3DUsers%2Co%3Dsss%2Cc%3Dit&ad_dn=CN%3DBongiorni+Alberto%2COU%3DServizi+ICT%2COU%3DOpenLdap%2CDC%3Dsssapisa%2CDC%3Dit

// %40 @ - %2F / - %5B [ - %5D ] - %3D = - %2C , 
// ldap_dn=uid = a.bongiorni , ou = Users , o = sss , c = it ad_dn=CN , Bongiorni Alberto , OU = Servizi ICT , OU = OpenLdap , DC = sssapisa , DC = it

		$suf=implode('',explode('.',$_REQUEST['uid'])); // esempio: abongiorni
		if (!empty($_REQUEST['origine'])){if ($_REQUEST['origine']=='LDAP'){
			error_reporting(0);
			$a=array();
			if (!empty($_REQUEST['ldap_nome_'.$suf])){$a['givenname']=$_REQUEST['ldap_nome_'.$suf];}
			if (!empty($_REQUEST['ldap_cognome_'.$suf])){$a['sn']=$_REQUEST['ldap_cognome_'.$suf];}
			if (!empty($_REQUEST['ldap_email_'.$suf])){$a['mail']=$_REQUEST['ldap_email_'.$suf];}
			if (!empty($_REQUEST['ldap_cf_'.$suf])){$a['x-codicefiscale']=$_REQUEST['ldap_cf_'.$suf];}
			if (!empty($_REQUEST['ldap_ka_'.$suf])){$a['departmentnumber']=$_REQUEST['ldap_ka_'.$suf];}
			if (!empty($_REQUEST['ldap_kr_'.$suf])){$a['businesscategory']=$_REQUEST['ldap_kr_'.$suf];}
			if (!empty($_REQUEST['ldap_df_'.$suf])){
				$d=$_REQUEST['ldap_df_'.$suf];
				$st=substr($d,6,4).substr($d,3,2).substr($d,0,2);
				$a['x-scadenzatempo']=$st.'220000.515Z'; 
			}
			if (!empty($_REQUEST['ldap_samba_'.$suf])){$a['sambaacctflags']=$_REQUEST['ldap_samba_'.$suf];}
			if (!empty($_REQUEST['ldap_telefono_'.$suf])){$a['telephonenumber']=$_REQUEST['ldap_telefono_'.$suf];}
			if (!empty($_REQUEST['ldap_cellulare_'.$suf])){$a['mobile']=$_REQUEST['ldap_cellulare_'.$suf];}
			if (!empty($_REQUEST['ldap_creatoreUid_'.$suf])){$a['x-creatoreuid']=$_REQUEST['ldap_creatoreUid_'.$suf];}
			if (!empty($_REQUEST['ldap_description_'.$suf])){$a['description']=$_REQUEST['ldap_description_'.$suf];}
			if (!empty($_REQUEST['ldap_dn'])){if ($_REQUEST['ldap_dn']!=''){
				ldap_mod_replace($ldap_conn,$_REQUEST['ldap_dn'],$a);
				$e=get_ldap_error('LDAP','ldap_mod_replace<br>');
				if ($e!=''){
					$s.=$e;
					$s.=get_alert('Modifica LDAP <strong>'.$_REQUEST['uid'].'</strong> NON modificato<br />','danger');
				} else {
					$s.=get_alert('Modifica LDAP <strong>'.$_REQUEST['uid'].'</strong> modificato','success');
					
					// aggiorno le tabelle (viste materializzate)
					// --- ANAGRAFICHE_DA_LDAP
					$sql="UPDATE C##SSS_IMPORT.ANAGRAFICHE_DA_LDAP SET DT_INS=sysdate";
					if (!empty($_REQUEST['ldap_nome_'.$suf])){$sql.=",givenname='".$_REQUEST['ldap_nome_'.$suf]."'";}
					if (!empty($_REQUEST['ldap_cognome_'.$suf])){$sql.=",sn='".$_REQUEST['ldap_cognome_'.$suf]."'";}
					if (!empty($_REQUEST['ldap_email_'.$suf])){$sql.=",mail='".$_REQUEST['ldap_email_'.$suf]."'";}
					if (!empty($_REQUEST['ldap_cf_'.$suf])){$sql.=",cf='".$_REQUEST['ldap_cf_'.$suf]."'";}
					if (!empty($_REQUEST['ldap_ka_'.$suf])){$sql.=",departmentnumber='".$_REQUEST['ldap_ka_'.$suf]."'";}
					if (!empty($_REQUEST['ldap_kr_'.$suf])){$sql.=",businesscategory='".$_REQUEST['ldap_kr_'.$suf]."'";}
					if (!empty($_REQUEST['ldap_df_'.$suf])){$sql.=",scadenzatempo='".$st."220000.515Z'";}
					if (!empty($_REQUEST['ldap_samba_'.$suf])){$sql.=",sambaacctflags='".$_REQUEST['ldap_samba_'.$suf]."'";}
					if (!empty($_REQUEST['ldap_telefono_'.$suf])){$sql.=",telephonenumber='".$_REQUEST['ldap_telefono_'.$suf]."'";}
					if (!empty($_REQUEST['ldap_cellulare_'.$suf])){$sql.=",mobile='".$_REQUEST['ldap_cellulare_'.$suf]."'";}
					$sql.=" WHERE LDAP_UID='".$_REQUEST['uid']."'";
					if (!empty($_REQUEST['ldap_cf_'.$suf])){$sql.=" or CF='".$_REQUEST['ldap_cf_'.$suf]."'";}
					$a=load_db($conn_new,$sql,'o');
					
					// --- AB_CAN
					$sql="UPDATE C##SSS_IMPORT.AB_CAN SET DT_REFRESH=sysdate, LDAP_UID='".$_REQUEST['uid']."'";
					if (!empty($_REQUEST['ldap_nome_'.$suf])){$sql.=",NOME='".$_REQUEST['ldap_nome_'.$suf]."'";}
					if (!empty($_REQUEST['ldap_cognome_'.$suf])){$sql.=",COGNOME='".$_REQUEST['ldap_cognome_'.$suf]."'";}
					if (!empty($_REQUEST['ldap_email_'.$suf])){$sql.=",MAIL='".$_REQUEST['ldap_email_'.$suf]."'";}
					if (!empty($_REQUEST['ldap_cf_'.$suf])){$sql.=",COD_FISC='".$_REQUEST['ldap_cf_'.$suf]."'";}
					if (!empty($_REQUEST['ldap_ka_'.$suf])){
						$sql.=",KA='".$_REQUEST['ldap_ka_'.$suf]."'";
						$indice_ka=array_search($_REQUEST['ldap_ka_'.$suf],$_SESSION['IAM']['lista_afferenze']['K']);	
						if ($indice_ka !== false){$sql.=",DA='".$_SESSION['IAM']['lista_afferenze']['DA'][$indice_ka]."'";}					
					}
					if (!empty($_REQUEST['ldap_kr_'.$suf])){
						$sql.=",KR='".$_REQUEST['ldap_kr_'.$suf]."'";
						$indice_kr=array_search($_REQUEST['ldap_kr_'.$suf],$_SESSION['IAM']['lista_ruoli']['K']);	
						if ($indice_kr !== false){$sql.=",DR='".$_SESSION['IAM']['lista_ruoli']['DR'][$indice_kr]."'";}					
					}
					if (!empty($_REQUEST['ldap_df_'.$suf])){$sql.=",DT_RAP_FIN=to_date('".$st."','YYYYMMDD')";}
					if (!empty($_REQUEST['ldap_telefono_'.$suf])){$sql.=",TEL='".$_REQUEST['ldap_telefono_'.$suf]."'";}
					if (!empty($_REQUEST['ldap_cellulare_'.$suf])){$sql.=",CELL='".$_REQUEST['ldap_cellulare_'.$suf]."'";}
					$sql.=" WHERE LDAP_UID='".$_REQUEST['uid']."'";
					if (!empty($_REQUEST['ldap_cf_'.$suf])){$sql.=" or COD_FISC='".$_REQUEST['ldap_cf_'.$suf]."'";}
					$a=load_db($conn_new,$sql,'o');
					
				}
			}}
			// --- AD
			$a=array();
			if (!empty($_REQUEST['ldap_nome_'.$suf])){$a['givenname']=$_REQUEST['ldap_nome_'.$suf]."'";}
			if (!empty($_REQUEST['ldap_cognome_'.$suf])){$a['sn']=$_REQUEST['ldap_cognome_'.$suf]."'";}
			if (!empty($_REQUEST['ldap_email_'.$suf])){$a['mail']=$_REQUEST['ldap_email_'.$suf]."'";}
			if (!empty($_REQUEST['ldap_cf_'.$suf])){$a['xcodicefiscale']=$_REQUEST['ldap_cf_'.$suf]."'";}
			if (!empty($_REQUEST['ldap_ka_'.$suf])){$a['departmentnumber']=$_REQUEST['ldap_ka_'.$suf]."'";}
			if (!empty($_REQUEST['ldap_kr_'.$suf])){$a['businesscategory']=$_REQUEST['ldap_kr_'.$suf]."'";}
			if (!empty($_REQUEST['ldap_df_'.$suf])){
				$d=$_REQUEST['ldap_df_'.$suf];
				$st=substr($d,6,4).substr($d,3,2).substr($d,0,2);
				$a['xscadenzatempo']=$st.'200000.0Z'; 
				$ae = date_create_from_format("d/m/Y", $d);
				$sec = strtotime(substr($st,0,4).'-'.substr($st,4,2).'-'.substr($st,6,2));
				$ae = (((float)$sec + 11644473600) * 10000000);
				$a['accountexpires']=sprintf('%018.0f',$ae);
			}
			// $a['sambaacctflags']=$_REQUEST['ldap_samba_'.$suf];
			if (!empty($_REQUEST['ldap_telefono_'.$suf])){$a['telephonenumber']=$_REQUEST['ldap_telefono_'.$suf]."'";}
			if (!empty($_REQUEST['ldap_cellulare_'.$suf])){$a['mobile']=$_REQUEST['ldap_cellulare_'.$suf]."'";}
			// $a['x-creatoreuid']=$_REQUEST['ldap_creatoreUid_'.$suf]."'";
			if (!empty($_REQUEST['ldap_description_'.$suf])){$a['description']=$_REQUEST['ldap_description_'.$suf]."'";}
			if (!empty($_REQUEST['ad_dn'])){if ($_REQUEST['ad_dn']!=''){
				$adBind = ldap_bind($ad_conn,'sssapisa.it/OpenLdap/Servizi ICT/User Read','readuser');	// administrator/aDuNo2016tt
				$e=get_ldap_error('AD','ldap_bind<br>');if ($e!=''){$s.=$e;}
				ldap_mod_replace($ad_conn,$_REQUEST['ad_dn'],$a);
				$e=get_ldap_error('AD','ldap_mod_replace<br>');if ($e!=''){$s.=$e;}
				if ($e!=''){
					$s.=get_alert('Modifica AD <strong>'.$_REQUEST['uid'].'</strong> NON modificato<br />','danger');
				} else {
					$s.=get_alert('Modifica AD <strong>'.$_REQUEST['uid'].'</strong> modificato','success');
				}
			}}
			
			error_reporting(E_ALL);
		} else {
			$s.='<div class="row"><div class="col-sm-12 alert alert-success">LDAP GUEST modificato</div></div>';
		}}
	}
	return $s;
}
function get_imp_old(){ 					// @@@ disabilitare se in produzione - visualizza il VECCHIO repository degli account registrati in LDAP
	global $conn_new, $conn_sl;
	$s="";
	// error_reporting(0);

	// aggiorna i dati della tabella c##sss_import.anagrafiche_da_ldap
	$sql="BEGIN C##SSS_IMPORT.AB_AGGIORNA_ADL; COMMIT; END;"; 
	$parsed = oci_parse($conn_new, $sql);		
	$r = oci_execute($parsed); 					
	$oe = oci_error($parsed);
	if (is_array($oe)){
		$msg=$sql.'<br /><pre>'.print_r($oe,true).'</pre>';
		tolog($msg);
		$s=get_alert($msg,'danger');
	} else {
		$msg='Aggiornamento vista LDAP - completato';
		$s=get_alert($msg,'success');
	}

//	// refresh dizionario anagrafiche attive
//	$sql="select a.*, to_char(a.DT_RAP_FIN, 'dd/mm/yyyy') as DTF from c##sss_import.ab_can a";
//	$a=load_db($conn_new,$sql,'o');
//	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
//		$_SESSION['IAM']['ab_can']=$a[2];
//	}

	// Mail Account dallo storico
	/*
	-- storico_ldap.v_MailAaccount_ex source

	CREATE OR REPLACE
	ALGORITHM = UNDEFINED VIEW `v_MailAaccount_ex` AS
	select
			`ma`.`id` AS `id`,
			`ma`.`nome` AS `uid`,
			`ma`.`idAnagrafe` AS `idAnagrafe`,
			`ma`.`redirezione` AS `redirezione`,
			`ma`.`password` AS `password`,
			`ma`.`cryptpasswd` AS `cryptpasswd`,
			`ma`.`apertura` AS `apertura`,
			`ma`.`chiusura` AS `chiusura`,
			`a`.`cognome` AS `cognome`,
			`a`.`nome` AS `nome`,
			`g`.`id` AS `idTblGID`,
			`g`.`nome` AS `gid_k`,
			`g`.`descrizione` AS `descrizione`,
			cast(from_unixtime(`ma`.`apertura`) as date) AS `inizio`,
			cast(from_unixtime(`ma`.`chiusura`) as date) AS `fine`
	from
			((`tblMailAccount` `ma`
	left join `tblAnagrafe` `a` on
			((`ma`.`idAnagrafe` = `a`.`id`)))
	left join `tblGID` `g` on
			((`a`.`idGID` = `g`.`id`)))
	order by
			`ma`.`apertura`;
	*/		
	$j=[];
	$sql="select * from storico_ldap.v_MailAaccount_ex order by inizio desc, cognome, nome";
	$a=mysqli_query($conn_sl,$sql);
	// if (!empty(mysqli_error($conn_sl))){$s.='<br />'.mysqli_error($conn_sl);}
	if (mysqli_num_rows($a) > 0) {	// dati trovati
		foreach($a as $row){
			$j[]=$row;
		}
	}

	// Alias dallo storico
	/*
	-- storico_ldap.v_alias source

	CREATE OR REPLACE
	ALGORITHM = UNDEFINED VIEW `v_alias` AS
	select
			`ta`.`id` AS `idAlias`,
			`ta`.`nome` AS `nomeAlias`,
			`ta`.`attivo` AS `attivo`,
			`ta`.`idMailAccount` AS `idMailAccount`,
			`ma`.`nome` AS `uid`,
			`a`.`cognome` AS `cognome`,
			`a`.`nome` AS `nome`,
			`g`.`nome` AS `gid_k`,
			`g`.`descrizione` AS `descrizione`,
			cast(from_unixtime(`ma`.`apertura`) as date) AS `inizio`,
			cast(from_unixtime(`ma`.`chiusura`) as date) AS `fine`
	from
			(((`tblAlias` `ta`
	left join `tblMailAccount` `ma` on
			((`ta`.`idMailAccount` = `ma`.`id`)))
	left join `tblAnagrafe` `a` on
			((`ma`.`idAnagrafe` = `a`.`id`)))
	left join `tblGID` `g` on
			((`a`.`idGID` = `g`.`id`)));
	*/
	$ja=[];
	$sql="select * from storico_ldap.v_alias";
	$a=mysqli_query($conn_sl,$sql);
	// if (!empty(mysqli_error($conn_sl))){$s.='<br />'.mysqli_error($conn_sl);}
	if (mysqli_num_rows($a) > 0) {	// dati trovati
		foreach($a as $row){
			$ja[]=$row;
		}
	}

	// $myfile = fopen('old_account.json', "r");
	// $f=fread($myfile,filesize('old_account.json'));
	// $je=json_decode($f);
	// $js=safe_json_encode($je->DATA);
	// $j=json_decode($js,true);
	// $jas=safe_json_encode($je->ALIAS);
	// $ja=json_decode($jas,true);
	// fclose($myfile);
	
/*
-- tblAnagrafe (a): 		id, cognome, nome, idStrutture, idPosizioni, idGID, attivo
-- tblMailAccount (ma): 	id, nome, idAnagrafe, redirezione, password, cryptpasswd, apertura, chiusura
-- tblGID (g): 				id, nome, descrizione
-- tblAlias (ta):	 		id, nome, idMailAccount, attivo

-- account
select ma.id, ma.nome as uid, ma.idAnagrafe, ma.redirezione, ma.password, ma.cryptpasswd, ma.apertura, ma.chiusura, a.cognome, a.nome, g.id as idTblGID, g.nome as gid_k, g.descrizione, DATE(from_unixtime(ma.apertura)) as inizio, DATE(from_unixtime(ma.chiusura)) as fine from mail.tblMailAccount as ma left join mail.tblAnagrafe a on ma.idAnagrafe = a.id left join mail.tblGID g on a.idGID = g.id order by ma.apertura;

-- alias
select ta.id as idAlias, ta.nome as nomeAlias, ta.attivo, ma.nome as uid, a.cognome, a.nome, g.nome as gid_k, g.descrizione, DATE(from_unixtime(ma.apertura)) as inizio, DATE(from_unixtime(ma.chiusura)) as fine from mail.tblAlias ta left join mail.tblMailAccount as ma on ta.idMailAccount = ma.id left join mail.tblAnagrafe a on ma.idAnagrafe = a.id left join mail.tblGID g on a.idGID = g.id;
*/	

	$s.='<div class="row bg-light">';
		$s.='<div class="col-sm-2">';
			$s.='<button class="btn btn-info btn-sm btn-block" act="toggleDTX" tabledom="tb-old-account">Toggle DataTables</button>';
		$s.='</div>';
		$s.='<div class="col-sm-2">';
		$s.='</div>';
		$s.='<div class="col-sm-2">';
		$s.='</div>';
		$s.='<div id="alert-old-account" class="col-sm-6"></div>';
	$s.='</div>';

	$s.='<table class="table table-striped table-sm table-responsive" id="tb-old-account"><thead>';
		$s.='<tr>';
			$s.='<th>attivo</th>';
			$s.='<th>ab_can</th>';
			$s.='<th>classe_cmg</th>';
			$s.='<th>uid</th>';
			$s.='<th>cognome</th>';
			$s.='<th>nome</th>';
			$s.='<th>apertura</th>';
			$s.='<th>chiusura</th>';
			$s.='<th>gruppo</th>';
			$s.='<th>alias</th>';
		$s.='</tr></thead><tbody>';
	foreach($j as $oa){
		$x='';
		$class='';
		$s.='<tr>';
			$apertura=date('Y/m/d',$oa['apertura']);
			$chiusura=date('Y/m/d',$oa['chiusura']);
			if ($chiusura=='1970/01/01'){$chiusura='';}
			if ($chiusura != '' and $chiusura < date('Y/m/d')){
				$class=' class="bg-dark text-white"';
				$x.='0';
			} else {
				$x='1';
			}
			// check se esiste in ANAGRAFICHE_DA_LDAP
			$s.='<td>'.$x.'</td>';
			$title=''; $class=''; $classe_cmg='';
			$in_ab_can=array_search($oa['uid'],$_SESSION['IAM']['ab_can']['LDAP_UID']);		// anagrafiche attive
			$in_ab_csn=array_search($oa['uid'],$_SESSION['IAM']['ab_csn']['LDAP_UID']);		// anagrafiche scadute
			$in_cmg_bkp=array_search($oa['uid'],$_SESSION['IAM']['cmg_bkp']['CMG_UID']);	// communigate
			if ($in_ab_can !== false){
				$in_ab_cxn=$in_ab_can;
				$s_cxn='ab_can';
			} else {
				if ($in_ab_csn !== false){
					$in_ab_cxn=$in_ab_csn;
					$s_cxn='ab_csn';
				} else {
					$in_ab_cxn=false;
				}
			}
			if (empty($oa['uid'])){
				$im='broken.jpg';
			} else {
				$ff='';
				try {
					$ff = file_get_contents("https://dotnetu.local/ms/getfoto.php?toc===AW55EVNdEeBNmaF1zIxIHQsBzU&uid=".$oa['uid']);
					$im='data:image/jpeg;base64,'.$ff;
				} catch(Exception $e) {
					// $errore.='<br />'.$e->getMessage();
					$im='broken.jpg';
				}
			}
			$dt="<img class='img-fluid logo' src='".$im."' />";
			$tt=$oa['uid'].' ('.$oa['cognome'].' '.$oa['nome'].' non trovato)';
			if ($in_cmg_bkp !== false){
				$classe_cmg=$_SESSION['IAM']['cmg_bkp']['CLASSE'][$in_cmg_bkp];
			}
			if ($in_ab_cxn !== false){
				$tt=$_SESSION['IAM'][$s_cxn]['NOME'][$in_ab_cxn].' '.$_SESSION['IAM'][$s_cxn]['COGNOME'][$in_ab_cxn];
				$dt.="<br />Ruolo: ".$_SESSION['IAM'][$s_cxn]['KR'][$in_ab_cxn].' '.$_SESSION['IAM'][$s_cxn]['DR'][$in_ab_cxn];
				$dt.="<br />Afferenza: ".$_SESSION['IAM'][$s_cxn]['KA'][$in_ab_cxn].' '.$_SESSION['IAM'][$s_cxn]['DA'][$in_ab_cxn];
				// $dt.="<br />Inquadramento: ".$_SESSION['IAM'][$s_cxn]['KI'][$in_ab_cxn].' '.$_SESSION['IAM'][$s_cxn]['DI'][$in_ab_cxn];
				$dt.="<br />Fine rapporto: ".substr($_SESSION['IAM'][$s_cxn]['DT_RAP_FIN'][$in_ab_cxn],0,10);
				if ($s_cxn=='ab_can'){
					$class=' class="font-weight-bold alert-success"';
					$s.='<td>1</td>';
				}
				if ($s_cxn=='ab_csn'){
					$class=' class="font-weight-bold alert-warning"';
					$s.='<td>0</td>';
				}
			} else {
				$s.='<td></td>';
			}
			$title=' data-toggle="popover" data-trigger="hover" data-html="true" title="'.$tt.'" data-content="'.$dt.'"';
				
			$s.='<td>'.$classe_cmg.'</td>';
			$s.='<td'.$title.$class.'>'.$oa['uid'].'</td>';
			$s.='<td>'.$oa['cognome'].'</td>';
			$s.='<td>'.$oa['nome'].'</td>';
			$s.='<td>'.$apertura.'</td>';
			$s.='<td>'.$chiusura.'</td>';
			$s.='<td>'.$oa['descrizione'].' ('.$oa['gid_k'].')</td>';
			$alias='';
			foreach($ja as $oaa){
				if ($oaa['idMailAccount']==$oa['id']){
					if ($oaa['attivo']==1){
						if ($alias!=''){$alias.=', ';}
						$alias.=$oaa['nomeAlias'];
					}
				}
			}
			$s.='<td>'.$alias.'</td>';
		$s.='</tr>';
	}
	$s.='</tbody></table>';
	
// error_reporting(E_ALL);
	return $s;
}
/*
function sistema_ldap_accenti_case(){	// @@@ disabilitata
	if (true){
		// @@@@ funzione sospesa
		$b = '';
		$stl='bg-warning text-center text-white';
		$msg='Funzione disabilitata';
		$tit='<h2>Sistema LDAP, accenti e case</h2>';
		$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $b);
		return json_encode($r);	
	}
	global $conn_new, $ip_ldap;
	$err='';
	$out='';
	try {
		$ds = ldap_connect($ip_ldap,389);
	} catch (Exception $e) {
		$err=$e->getMessage();
	}
	if ($ds and $err=='') {
		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
		$ldapBind = ldap_bind($ds,'cn=manager,o=sss,c=it','fugasse');

		// aggiorno la copia di LDAP su DB che sarà poi eventualmente sovrascritta dalla procedura AGGIORNA_CARRIERE
		$sql="SELECT * FROM c##sss_import.anagrafiche_da_ldap ORDER BY SN, GIVENNAME";
		$a=load_db($conn_new,$sql,'o',false);
		if ($a[0] > 0 and $a[1] > 0) { // 0=col, 1=row
			$out.='<hr />Controllo <strong>'.$a[1].'</strong> righe<hr><br />uid - givenname - sn - cn - displayname - description ==> givenname sn description<hr />';
			// 195 = (pre)
			// 129 = A
			// 161 = a
			// 179 = o
			// 177 = n
			// 173 = i
			// 162 180 = a
			// 163 = a
			// $atr=array('À'=>'A','├'=>'A','ü'=>'u','í'=>'i','È'=>'E','Ì'=>'I','Ò'=>'O','Ù'=>'U','à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','É'=>'E','é'=>'e',','=>'');
			$atr=array('À'=>'A','È'=>'E','Ì'=>'I','Ò'=>'O','Ù'=>'U','à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','É'=>'E','é'=>'e',','=>'',chr(92)=>'');
			for ($i = 0; $i < $a[1]; $i++) {
				// update su LDAP (@@@ cn e displayname = givenname sn)
				$sn=ucwords(strtolower($a[2]['SN'][$i]));
				$givenname=ucwords(strtolower($a[2]['GIVENNAME'][$i]));
				$description=ucwords(strtolower($a[2]['DESCRIPTION'][$i]));
				foreach (array_keys($atr) as $c){
					$sn=trim(implode($atr[$c],explode($c,$sn)));
					$givenname=trim(implode($atr[$c],explode($c,$givenname)));
					$description=trim(implode($atr[$c],explode($c,$description)));
				}
				$sn=ucwords(strtolower(trim($sn)));
				$givenname=ucwords(strtolower(trim($givenname)));
				if ($description==''){$description=trim($givenname.' '.$sn);}
				$description=ucwords(strtolower(trim($description)));

				// -- controllo se i caratteri contenuti sono tutti corretti
				$o=array('sn' => $sn, 'givenname' => $givenname, 'description' => $description);
				$e=''; $ee=''; 
				foreach ($o as $k => $v){
					$t=ab_str_utf8_ascii($v);
					if ($t != $v){
						$ee.='<br /><strong>'.$k.'</strong>: '.$v.' => '.$t;
						$$k = $t;
					}
				}
				if ($ee!=''){
					$out.='<br><span class="text-danger">'.$a[2]['LDAP_UID'][$i].'</span>'.$ee;
				}

				$cn=trim($givenname.' '.$sn);
				$displayname=trim($givenname.' '.$sn);

				if 	(	$sn != $a[2]['SN'][$i] 
						or $givenname != $a[2]['GIVENNAME'][$i] 
						or $cn != $a[2]['CN'][$i] 
						or $displayname != $a[2]['DISPLAYNAME'][$i]
						or $description != $a[2]['DESCRIPTION'][$i] ) {
					$out.='<br />'.$a[2]['LDAP_UID'][$i].' '.$a[2]['GIVENNAME'][$i].' '.$a[2]['SN'][$i].' '.$a[2]['CN'][$i].' '.$a[2]['DISPLAYNAME'][$i].' '.$a[2]['DESCRIPTION'][$i].' ==> '.$givenname.' '.$sn.' '.$description;
					$dn='uid='.$a[2]['LDAP_UID'][$i].',ou=users,o=sss,c=it';
					$entry = array();
					$entry['sn'] = array(trim($sn));
					$entry['givenname'] = array(trim($givenname));
					$entry['cn'] = array(trim($cn));
					$entry['displayname'] = array(trim($displayname));
					$entry['description'] = array(trim($description));
					try {
						$results = ldap_mod_replace($ds,$dn,$entry); // aggiorno LDAP
						// aggiorno la tabella backup di LDAP che sarà riaggiornata dal database in automatico entro 2 ore
						$e=ldap_errno($ds);	// error number
						if ($e){	// se e != 0
							ldap_get_option($ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err); // leggo dettagli errore
							$out.=get_alert('<span class="text-danger">'.ldap_error($ds).' ('.$e.')<br>'.$err.'</span>','danger');
						}
					} catch(Exception $e) {
						$out.='<br />'.$e->getMessage();
					}

					$sql="update c##sss_import.anagrafiche_da_ldap set sn='".($sn==''?'null':str_replace("'","''",$sn))."', givenname='".($givenname==''?'null':str_replace("'","''",$givenname))."', cn='".($cn==''?'null':str_replace("'","''",$cn))."', displayname='".($displayname==''?'null':str_replace("'","''",$displayname))."', description='".($description==''?'null':str_replace("'","''",$description))."' where ldap_uid='".$a[2]['LDAP_UID'][$i]."'";
					$b=load_db($conn_new,$sql,'o',false);

				}
			}
		}
	}

	$b = '';
	if ($err==''){
		$stl='bg-success text-center text-white';
		$msg='Ldap sistemati:<br />'.$out;
	} else {
		$stl='bg-danger text-center text-white';
		$msg=json_decode($err);
	}
	$tit='<h2>Sistema LDAP, accenti e case</h2>';
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $b);
	return json_encode($r);
}
*/
// --- eccezioni ---
function get_nuova_eccezione(){		// form
	global $conn_new, $ldap_conn;
	$iu='i';	// i = insert, u = update (c##sss_import.carriere_eccezioni)
	$upd=false;
	if (!empty($_REQUEST['cf'])){
		// controllo se il CF esiste già in carriere_eccezioni
		$sql="select * from c##sss_import.carriere_eccezioni where cod_fisc = '".trim($_REQUEST['cf'])."'";	
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {$iu='u';$upd=true;}

		$sql="select a.*, case when trunc(dt_rap_ini) <= trunc(sysdate) and trunc(dt_rap_fin) >= trunc(sysdate) then 1 else 0 end attivo, to_char(dt_rap_ini,'DD/MM/YYYY') di, to_char(dt_rap_fin,'DD/MM/YYYY') df, to_char(dt_rap_ini,'YYYYMMDD') di_r, to_char(dt_rap_fin,'YYYYMMDD') df_r, to_char(dt_nascita,'DD/MM/YYYY') dtn from c##sss_import.";
		if ($iu=='u'){$sql.='carriere_eccezioni';} else {$sql.='ab_cn';}
		$sql.=" a where cod_fisc='".trim($_REQUEST['cf'])."' and kr='".$_REQUEST['kr']."'";
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {$upd=true;}
	}
	$s='<br />';
	$s.='<form id="f_ge" class="">';
	
		$s1='dark';
		if ($upd){
			$di=$a[2]['DI_R'][0];
			$df=$a[2]['DF_R'][0];
			if ($di <= date("Ymd") and $df >= date("Ymd")){$s1='success';} else {$s1='danger';}
		}
		$s.='<div class="row alert alert-'.$s1.'">';
			$s.='<div class="col-sm-2">';	// colonna di sinistra (immagine)
			
				if ($upd){

					$img64 = file_get_contents('https://dotnetu.local/gfoto/gfoto.php?toc===AW55EVNdEeBNmaF1zIxIHQsBzU&cf='.$a[2]['COD_FISC'][0]);
					if (strlen($img64) > 64){
						$s.='<img class="img-fluid img-thumbnail" src="data:image/jpeg;base64,'.$img64.'"/>';
					} else {
						$s.='<img class="img-fluid img-thumbnail" src="broken.gif"/>';
					}

//					// cerco ldap_uid collegato al CF
//					$sql="select LDAP_UID from c##sss_import.ab_cn where cod_fisc = '".$a[2]['COD_FISC'][0]."'";	
//					$aa=load_db($conn_new,$sql,'o');
//					if ($aa[0]>0 and $aa[1]>0) {
//						clearstatcache();
//						$pim='/rac/img/';
//						if (empty($aa[2]['LDAP_UID'][0])){
//							$im=$pim.'broken.jpg';
//						} else {
//							$im=$pim.$aa[2]['LDAP_UID'][0].'.jpg'; 									
//							if (!file_exists('/var/www/html'.$im)) {$im=$pim.'broken.jpg';}
//						}
//						$s.='<img class="img-fluid img-thumbnail" src="'.$im.'" />';
//					}
				}
			$s.='</div>';
			$s.='<div class="col-sm-6">';	// colonna di centro (dati eccezione)
				$s.='<div class="row alert alert-secondary">';
					$l='NUOVA'; if ($iu=='u'){$l='MODIFICA';}
					$s.='<div class="col-sm-12 text-center"><strong>'.$l.' ECCEZIONE</strong></div>';
				$s.='</div>';

			// GEST
				$s.='<div class="row bg-light py-2">';
					$s.='<div class="col-sm-4 text-right">Gestionale:</div>';
					$s.='<div class="col-sm-8">';
						$s.='<input type="text" name="eczn_gest" id="eczn_gest" class="form-control form-control-sm" value="ECZN" readonly />';
					$s.='</div>';
				$s.='</div>';
				
			// LDAP_UID
				$s.='<div class="row bg-light py-2">';
					$s.='<div class="col-sm-4 text-right">UID:</div>';
					$s.='<div class="col-sm-8">';
						$s.='<input type="text" name="eczn_ldap_uid" id="eczn_ldap_uid" class="form-control form-control-sm" value="'.(empty($aa[2]['LDAP_UID'][0])?'':$aa[2]['LDAP_UID'][0]).'" readonly />';
					$s.='</div>';
				$s.='</div>';
			
			// K, ID_AB, 
			// NOME
				$s.='<div class="row bg-light">';
					$s.='<div class="col-sm-4 text-right">Nome:</div>';
					$s.='<div class="col-sm-8">';
						$s.='<input type="text" name="eczn_nome" id="eczn_nome" class="form-control form-control-sm" value="';
						if ($upd){$s.=htmlentities($a[2]['NOME'][0]);}
						$s.='" />';
					$s.='</div>';
				$s.='</div>';

			// COGNOME
				$s.='<div class="row bg-light">';
					$s.='<div class="col-sm-4 text-right">Cognome:</div>';
					$s.='<div class="col-sm-8">';
						$s.='<input type="text" name="eczn_cognome" id="eczn_cognome" class="form-control form-control-sm" value="';
						if ($upd){$s.=htmlentities($a[2]['COGNOME'][0]);}
						$s.='" />';
					$s.='</div>';
				$s.='</div>';

			// COD_FISC
				$s.='<div class="row bg-light">';
					$s.='<div class="col-sm-4 text-right">';
					$s.='<button class="btn btn-success btn-sm" act="cf_calcola" dom="sge" getf="eczn_nome,eczn_cognome,eczn_genere,eczn_dtn,eczn_comune">calcola</button>  ';
					$s.='Codice fiscale:';
					$s.='</div>';
					$s.='<div class="col-sm-4">';
						$s.='<input type="text" name="eczn_cf" id="eczn_cf" class="form-control form-control-sm" value="';
						if ($upd){$s.=$a[2]['COD_FISC'][0];}
						$s.='" />';
					$s.='</div>';
					$s.='<div class="col-sm-4">';
						$s.='<button class="btn btn-success btn-sm btn-block" act="cf_to_input" dom="eczn_cfout" getf="eczn_cf">-> genere, nascita, comune</button>';
					$s.='</div>';
				$s.='</div>';
			
			$s.='<div id="eczn_cfout" class="">';
				// GENERE
				$s.='<div class="row bg-light">';
					$s.='<div class="col-sm-4 text-right">Genere:</div>';
					$s.='<div class="col-sm-8">';
						$ss='';
						$generi=array('M'=>'Maschio','F'=>'Femmina');
						$gk=array_keys($generi);
						$gv=array_values($generi);
						for ($j=0; $j < count($generi); $j++) {
							$ss.='<option data-tokens="'.$gk[$j].'" value="'.$gk[$j].'"';
							if($upd){if ($gk[$j]==$a[2]['GENERE'][0]){$ss.=' selected';}}
							$ss.='>'.$gv[$j].'</option>';
						}
						$s.='<select name="eczn_genere" id="eczn_genere" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep >
							<option data-tokens="0" value="0"></option>
							'.$ss.'
						</select>';
					$s.='</div>';
				$s.='</div>';

				// DT_NASCITA
				$s.='<div class="row bg-light">';
					$s.='<div class="col-sm-4 text-right">Data di nascita:</div>';
					$s.='<div class="col-sm-3">';
						$s.='<input type="text" name="eczn_dtn" id="eczn_dtn" class="form-control form-control-sm" value="';
						if ($upd){$s.=$a[2]['DTN'][0];}
						$s.='" dto />';
					$s.='</div>';
				$s.='</div>';

				// COMUNE, 
				$s.='<div class="row bg-light">';
					$s.='<div class="col-sm-4 text-right">Comune o stato estero:</div>';
					$s.='<div class="col-sm-8">';
						$ss='';
						if (!empty($_SESSION['IAM']['lista_comuni'])){
							for ($j=0; $j < count($_SESSION['IAM']['lista_comuni']['K']); $j++) {
								$ss.='<option data-tokens="'.$_SESSION['IAM']['lista_comuni']['K'][$j].'" value="'.$_SESSION['IAM']['lista_comuni']['K'][$j].'"';
								if($upd){if ($_SESSION['IAM']['lista_comuni']['K'][$j]==$a[2]['COMUNE'][0]){$ss.=' selected';}}
								$ss.='>'.$_SESSION['IAM']['lista_comuni']['D'][$j].'</option>';
							}
						}
						$s.='<select name="eczn_comune" id="eczn_comune" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep >
							<option data-tokens="0" value="0"></option>
							'.$ss.'
						</select>';
					$s.='</div>';
				$s.='</div>';
			$s.='</div>';

			// MAIL_ESTERNA
				$s.='<div class="row bg-light">';
					$s.='<div class="col-sm-4 text-right">Mail esterna:</div>';
					$s.='<div class="col-sm-8">';
						$s.='<input type="text" name="eczn_mailex" id="eczn_mailex" class="form-control form-control-sm" value="';
						if ($upd){$s.=$a[2]['MAIL_ESTERNA'][0];}
						$s.='" />';
					$s.='</div>';
				$s.='</div>';
				
			// KA, DA
			$s.='<div class="row bg-light">';
				$s.='<div class="col-sm-4 text-right">Afferenza:</div>';
				$s.='<div class="col-sm-8">';
					$ss='';
					if (!empty($_SESSION['IAM']['lista_afferenze'])){
						for ($j=0; $j < count($_SESSION['IAM']['lista_afferenze']['K']); $j++) {
							$ss.='<option data-tokens="'.$_SESSION['IAM']['lista_afferenze']['K'][$j].'" value="'.$_SESSION['IAM']['lista_afferenze']['K'][$j].'"';
							if($upd){if ($_SESSION['IAM']['lista_afferenze']['K'][$j]==$a[2]['KA'][0]){$ss.=' selected';}}
							$ss.='>'.$_SESSION['IAM']['lista_afferenze']['D'][$j].'</option>';
						}
					}
					$s.='<select name="eczn_ka" id="eczn_ka" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep >
						<option data-tokens="0" value="0"></option>
						'.$ss.'
					</select>';
				$s.='</div>';
			$s.='</div>';

			// KR, DR
			$s.='<div class="row bg-light">';
				$s.='<div class="col-sm-4 text-right">Ruolo:</div>';
				$s.='<div class="col-sm-8">';
					$ss='';
					if (!empty($_SESSION['IAM']['lista_ruoli'])){
						for ($j=0; $j < count($_SESSION['IAM']['lista_ruoli']['K']); $j++) {
							$ss.='<option data-tokens="'.$_SESSION['IAM']['lista_ruoli']['K'][$j].'" value="'.$_SESSION['IAM']['lista_ruoli']['K'][$j].'"';
							if($upd){if ($_SESSION['IAM']['lista_ruoli']['K'][$j]==$a[2]['KR'][0]){$ss.=' selected';}}
							$ss.='>'.$_SESSION['IAM']['lista_ruoli']['D'][$j].'</option>';
						}
					}
					$s.='<select name="eczn_kr" id="eczn_kr" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep >
						<option data-tokens="0" value="0"></option>
						'.$ss.'
					</select>';
				$s.='</div>';
			$s.='</div>';

			// DTR, KI, DI, KAS, KS, DS, KU, DU, TR, 
			// DT_RAP_INI, DT_RAP_FIN
			$s.='<div class="row bg-light">';
				$s.='<div class="col-sm-4 text-right">Data inizio:</div>';
				$s.='<div class="col-sm-3">';
					$s.='<input type="text" name="eczn_di" id="eczn_di" class="form-control form-control-sm" value="';
					if ($upd){$s.=$a[2]['DI'][0];}
					$s.='" dto />';
				$s.='</div>';
				$s.='<div class="col-sm-2 text-right">Data fine:</div>';
				$s.='<div class="col-sm-3">';
					$s.='<input type="text" name="eczn_df" id="eczn_df" class="form-control form-control-sm"';
					$s.=' value="';
						if ($upd){$s.=$a[2]['DF'][0];}
					$s.='"';
					$s.=' dfprec="';
						if ($upd){$s.=$a[2]['DF'][0];}
					$s.='"';
					$s.=' dto />';
				$s.='</div>';
			$s.='</div>';
			
			// NOTA
			$s.='<div class="row bg-light py-1">';
				$s.='<div class="col-sm-4 text-right">Nota:</div>';
				$s.='<div class="col-sm-8">';
					$s.='<textarea rows="5" name="eczn_nota" id="eczn_nota" class="form-control form-control-sm">';
					if ($upd){$s.=htmlentities($a[2]['NOTA'][0]);}
					$s.='</textarea>';
				$s.='</div>';
			$s.='</div>';
			if ($_SESSION['IAM']['uid_login']=='a.bongiorni' and $iu=='u'){
				$s.='<div class="row alert-secondary">';
					$s.='<div class="col-sm-3">usr_ins: <strong>'.$a[2]['ICT_USR_INS'][0].'</strong></div>';
					$s.='<div class="col-sm-3">dt_ins:  <strong>'.substr($a[2]['ICT_DT_INS'][0],0,10). '</strong></div>';
					$s.='<div class="col-sm-3">usr_mod: <strong>'.$a[2]['ICT_USR_MOD'][0].'</strong></div>';
					$s.='<div class="col-sm-3">dt_mod:  <strong>'.substr($a[2]['ICT_DT_MOD'][0],0,10). '</strong></div>';
				$s.='</div>';
			}
			$s.='</div>';
			$s.='<div class="col-sm-4">';	// colonna di destra (pulsanti e informazioni)
				$s.='<div class="container-fluid">';
					$s.='<div class="row">';
						$s.='<div class="col-sm-4">';
							$s.='<button class="btn btn-success btn-sm btn-block" act="sge" dom="sge" tab="ge"';
							if ($upd){$s.=' eczn_cf_ori="'.$a[2]['COD_FISC'][0].'"';}
							$s.='>Salva</button>';
						$s.='</div>';
						$s.='<div class="col-sm-4">';
							if ($_SESSION['IAM']['uid_login']=='a.bongiorni'){
								$s.='<button class="btn btn-danger btn-sm btn-block" act="sge" dom="mm" tab="ge" tipo="D">Salva in DISABLED</button>';
							}
						$s.='</div>';
						$s.='<div class="col-sm-4">';
							if ($iu=='u'){
								$s.='<button class="btn btn-danger btn-sm btn-block" act="de" cf="'.$a[2]['COD_FISC'][0].'" nc="'.$a[2]['NOME'][0].' '.$a[2]['COGNOME'][0].'" dom="mm">Cancella</button>';
							}
						$s.='</div>';
					$s.='</div>';
					$s.='<div class="row py-2"><div class="col-sm-12">'; // informazioni pregresse di eccezioni precedenti
						if (!empty($_REQUEST['cf'])){
							$sql="select * from (";
								$sql.="select 'e' tipo, a.*, to_char(a.dt_rap_fin, 'dd/mm/yyyy') df from c##sss_import.carriere_eccezioni a where a.COD_FISC='".trim($_REQUEST['cf'])."'";
							$sql.=" UNION ";
								$sql.="select 's' tipo, b.*, to_char(b.dt_rap_fin, 'dd/mm/yyyy') df from c##sss_import.carriere_eccezioni_storico b where b.COD_FISC='".trim($_REQUEST['cf'])."'";
							$sql.=") order by ICT_USR_INS";
							$a=load_db($conn_new,$sql,'o');
							if ($a[0]>0 and $a[1]>0) {
								$g=[['TIPO'],['KR'],['DF'],['NOTA']];
								$d=['Tipo','Ruolo','Scadenza','Nota'];
								$s.=get_table_data($a[2],$g,$d,'eccezioni_pregresse','o',false);
								// $s.=get_alert('Numero eccezioni gi&agrave; presenti (compreso storico): <strong>'.$a[2]['N'][0].'</strong','info');
							}
						}
					$s.='</div></div>';
					$s.='<div class="row"><div class="col-sm-12" id="sge"></div></div>';
				$s.='</div>';
			$s.='</div>';


	$s.='</form>';
	return $s;

}
function get_salva_nuova_eccezione() {
	global $conn_new;
	$s='<br />';
	$e=''; $w='';
	// eczn_gest, eczn_nome, eczn_cognome, eczn_cf, eczn_mailex, eczn_ka, eczn_kr, eczn_di, eczn_df, eczn_nota
	if (empty($_REQUEST['eczn_nome'])){$e.=get_alert('Manca <strong>Nome</strong>','danger');}
	if (empty($_REQUEST['eczn_cognome'])){$e.=get_alert('Manca <strong>Cognome</strong>','danger');}
	if (empty($_REQUEST['eczn_cf'])){$e.=get_alert('Manca <strong>Codice fiscale</strong>','danger');} else {
			$cf = new CheckCF();
			if ($cf->isFormallyCorrect($_REQUEST['eczn_cf'])) {
					// print('Codice Fiscale formally correct');
					// printf('Birth Day: %s',     $cf->getDayBirth());
					// printf('Birth Month: %s',   $cf->getMonthBirth());
					// printf('Birth Year: %s',    $cf->getYearBirth());
					$aa=$cf->getYearBirth();
					if ($aa > date('y')-15){$ce='19';} else {$ce='20';}
					$dt_nascita=$cf->getDayBirth().'/'.$cf->getMonthBirth().'/'.$ce.$cf->getYearBirth();
					if ($dt_nascita != $_REQUEST['eczn_dtn']){
						$w.='<div class="row"><div class="col-sm-12 alert alert-warning">La <strong>Data di nascita</strong> del cf non corrisponde ('.$dt_nascita.')</div></div>';
					}
					// printf('Birth Country: %s', $cf->getCountryBirth());
					$comune=strtoupper($cf->getCountryBirth());
					if ($comune != $_REQUEST['eczn_comune']){
						$w.='<div class="row"><div class="col-sm-12 alert alert-warning">Il <strong>Comune</strong> del cf non corrisponde ('.$comune.')</div></div>';
					}
					// printf('Sex: %s',           $cf->getSex());
					$genere=strtoupper($cf->getSex());
					if ($genere != $_REQUEST['eczn_genere']){
						$w.='<div class="row"><div class="col-sm-12 alert alert-warning">Il <strong>Genere</strong> del cf non corrisponde ('.$genere.')</div></div>';
					}
			} else {
					$e.=get_alert('<strong>Codice Fiscale</strong> errato','danger');
			}		

		//  if (strlen($_REQUEST['eczn_cf'])!=16){
		//  	$e.='<div class="row"><div class="col-sm-12 alert alert-danger">La lunghezza del <strong>Codice fiscale</strong> &egrave; errata</div></div>';
		//  }

	}
	if (empty($_REQUEST['eczn_genere'])){
		$w.=get_alert('Manca <strong>Genere</strong> (sar&agrave; calcolato dal CF)','warning');
		if (!empty($genere)){$_REQUEST['eczn_genere']=$genere;}
	}
	if (empty($_REQUEST['eczn_dtn'])){
		$w.=get_alert('Manca <strong>Data di nascita</strong> (sar&agrave; calcolata dal CF)','warning');
		if (!empty($dt_nascita)){$_REQUEST['eczn_dtn']=$dt_nascita;}
	}
	if (empty($_REQUEST['eczn_comune'])){
		$w.=get_alert('Manca il <strong>Comune</strong> (sar&agrave; calcolata dal CF)','warning');
		if (!empty($comune)){$_REQUEST['eczn_comune']=$comune;}
	}
	if (empty($_REQUEST['eczn_mailex'])){$w.=get_alert('Manca <strong>Mail esterna</strong>','warning');}
	if (empty($_REQUEST['eczn_ka'])){$e.=get_alert('Manca <strong>Afferenza</strong>','danger');}
	if (empty($_REQUEST['eczn_kr'])){$e.=get_alert('Manca <strong>Ruolo</strong>','danger');}
	if (empty($_REQUEST['eczn_di'])){
		$w.=get_alert('>Manca <strong>Data inizio</strong><br>Inserisco la data di oggi','warning');
		// imposto la data di inizio ad oggi
		$_REQUEST['eczn_di']=date("d/m/Y");
	}
	// calcolo data inizio rovesciata
	$dir=substr($_REQUEST['eczn_di'],6,4).substr($_REQUEST['eczn_di'],3,2).substr($_REQUEST['eczn_di'],0,2);

	$iu='i';
	// controllo se il CF esiste già in carriere_eccezioni
	$sql="select * from c##sss_import.carriere_eccezioni where cod_fisc = '".$_REQUEST['eczn_cf']."'";	
	$a=load_db($conn_new,$sql,'o');
	if ($a[0]>0 and $a[1]>0) {$iu='u';}	// almeno una riga e almeno una colonna

	if (empty($_REQUEST['eczn_df'])){$e.=get_alert('Manca <strong>Data fine</strong>','danger');} else {
		// calcolo data fine rovesciata
		$dfr=substr($_REQUEST['eczn_df'],6,4).substr($_REQUEST['eczn_df'],3,2).substr($_REQUEST['eczn_df'],0,2);
		if ($iu=='i'){
			// se sto inserendo una nuova eccezione non ammetto una con scadenza inferiore ad oggi
			if ($dfr < date("Ymd")){$e.=get_alert('Inserimento con Data fine <strong>scaduta</strong>','danger');}
		}
		if ($dfr < $dir){$e.=get_alert('Data fine minore di data inizio','danger');}
	}

	if ($e!=''){
		$s.=$e;
		return $s;
	}
	$s.=$w;
	
/*
$s.=htmlentities($x);	// lettura dato da db per html
$sql.=($_REQUEST['AAA']==''?'null':"'".implode("''",explode("'",iconv("UTF-8", "ISO-8859-1//TRANSLIT", $_REQUEST['AAA'])))); // prepara sql per postgres
*/

	$a1=array("eczn_nome","eczn_cognome","eczn_cf","eczn_genere","eczn_dtn","eczn_comune","eczn_mailex","eczn_ka","eczn_kr","eczn_di","eczn_df","eczn_nota");
	$a2=array("NOME","COGNOME","COD_FISC","GENERE","DT_NASCITA","COMUNE","MAIL_ESTERNA","KA","KR","DT_RAP_INI","DT_RAP_FIN","NOTA");

	$eczn_uid=(empty($_REQUEST['eczn_ldap_uid'])?'null':"'".$_REQUEST['eczn_ldap_uid']."'");

	$da='null';
	$i=array_search($_REQUEST['eczn_ka'],$_SESSION['IAM']['lista_afferenze']['KA']);
	if ($i !== false){$da=implode("''",explode("'",htmlentities($_SESSION['IAM']['lista_afferenze']['DA'][$i])));}
	// $sql="select distinct da from c##sss_import.ab_can where ka = '".$_REQUEST['eczn_ka']."'";
	// $sql="select descr da from c##sss_import.ab_didorgsedi where uo = '".$_REQUEST['eczn_ka']."' and lower(typ)='org'";	
	// $a=load_db($conn_new,$sql,'o');
	// if ($a[0]>0 and $a[1]>0) {$da="'".implode("''",explode("'",htmlentities($a[2]['DA'][0])))."'";}	

	$dr='null';
	$i=array_search($_REQUEST['eczn_kr'],$_SESSION['IAM']['lista_ruoli']['KR']);
	if ($i !== false){$dr=implode("''",explode("'",htmlentities($_SESSION['IAM']['lista_ruoli']['DR'][$i])));}
	// $sql="select distinct dr from c##sss_import.ab_can where kr = '".$_REQUEST['eczn_kr']."'";
	// $sql="select ruolo dr from c##sss_import.ab_ruolo_new where k_ruolo = '".$_REQUEST['eczn_kr']."'";
	// $a=load_db($conn_new,$sql,'o');
	// if ($a[0]>0 and $a[1]>0) {$dr="'".implode("''",explode("'",htmlentities($a[2]['DR'][0])))."'";}	

	$sql='';
	$tabella='c##sss_import.carriere_eccezioni';
	if (!empty($_REQUEST['tipo'])){if ($_REQUEST['tipo'] == 'D'){$tabella='c##sss_import.carriere_eccezioni_disable';}}
	if ($iu=='i'){
		$sql.="insert into ".$tabella." (GEST, ECZN_UID, ".implode(', ',$a2).", DA, DR, ICT_USR_INS, ICT_DT_INS) VALUES ('ECZN',".$eczn_uid.",";
		for ($i=0; $i < count($a1); $i++){
			if ($i>0){$sql.=', ';}
			if (substr($a2[$i],0,3)=='DT_'){ // data (tutte le date in questa tabella cominciano con DT_)
				$sql.=($_REQUEST[$a1[$i]]==''?'null':"to_date('".$_REQUEST[$a1[$i]]."','DD/MM/YYYY')");
			} else { // stringa (non ho mumeri)
				$sql.=($_REQUEST[$a1[$i]]==''?'null':"'".implode("''",explode("'",$_REQUEST[$a1[$i]]))."'");
			}
		}
		$sql.=", '".$da."', '".$dr."'";
		$sql.=", '".$_SESSION['IAM']['uid_login']."', to_date('".date("d/m/Y")."','DD/MM/YYYY')";
		$sql.=')';
		$msg='Eccezione inserita';
	}
	if ($iu=='u'){
		$sql.="update ".$tabella." set GEST='ECZN', ECZN_UID=".$eczn_uid.", ";
		for ($i=0; $i < count($a1); $i++){
			if ($i>0){$sql.=', ';}
			if (substr($a2[$i],0,3)=='DT_'){ // data
				$sql.=$a2[$i].'='.($_REQUEST[$a1[$i]]==''?'null':"to_date('".$_REQUEST[$a1[$i]]."','DD/MM/YYYY')");
			} else { // stringa (non ho mumeri)
				$sql.=$a2[$i]."=".($_REQUEST[$a1[$i]]==''?'null':convert_utf8("'".implode("''",explode("'",$_REQUEST[$a1[$i]]))."'"));
			}
		}
		$sql.=", DA='".$da."', DR='".$dr."'";
		$sql.=", ICT_DT_MOD=to_date('".date("d/m/Y")."','DD/MM/YYYY'), ICT_USR_MOD='".$_SESSION['IAM']['uid_login']."'";
		// in c##sss_import.carriere_eccezioni il CF dovrebbe essere univoco (su un solo record)
		if (empty($_REQUEST['eczn_cf_ori'])){
			$sql.=" where cod_fisc='".$_REQUEST['eczn_cf']."'";
		} else {
			$sql.=" where cod_fisc='".$_REQUEST['eczn_cf_ori']."'";
		}
		$msg='Eccezione modificata';
	}
	if ($_SESSION['IAM']['uid_login']=='a.bongiorni'){$s.=get_alert($sql);}

	// $a=load_db($conn_new,$sql,'o');
	$parsed = oci_parse($conn_new, $sql);		
	$r = oci_execute($parsed); 					
	$oe = oci_error($parsed);
	if (is_array($oe)){
		$msg=$sql.'<br /><pre>'.print_r($oe,true).'</pre>';
		tolog($msg);
		$s.='<br />'.get_alert($msg,'danger');
	} else {
		// $msg='Aggiornamento completato';
		$s.='<br />'.get_alert($msg,'success');
	}
	// ricalcolo tabelle?
	
	// la data di scadenza inserita è attiva (presente o futura) o scaduta (passata) ?
	// se eccezione attiva
		// controllo se esiste in ab_can (se no lo inserisco) (se si lo aggiorno)
		// controllo se esiste in anagrafiche_da_ldap (se no lo inserisco) (se si lo aggiorno)
	// se eccezione scaduta (modifica di carriere_eccezioni e spostamento nello storico)
	// se esiste aggiorno ldap
	// se esiste aggiorno AD
	
	return $s;	
}
function get_lista_eccezioni(){
	global $conn_new;
/*
	-- ricerca duplicati in carriere_eccezioni
	select * from c##sss_import.carriere_eccezioni where cod_fisc in (
		select cod_fisc from c##sss_import.carriere_eccezioni group by cod_fisc having count(cod_fisc) > 1 
	)
*/	
	if (!empty($_REQUEST['storico'])){$t='carriere_eccezioni_storico';} else {$t='carriere_eccezioni';}
	$s='';
	$sql="select distinct eczn_uid as LDAP_UID, GEST, COGNOME, NOME, COD_FISC, MAIL_ESTERNA, KA, DA, KR, DR, NOTA, case when trunc(dt_rap_ini) <= trunc(sysdate) and trunc(dt_rap_fin) >= trunc(sysdate) then 1 else 0 end attivo, to_char(dt_rap_fin,'yyyy/mm/dd') df, 1 ep, ICT_USR_INS, to_char(ict_dt_ins,'yyyy/mm/dd') ICT_DT_INS, ICT_USR_MOD, to_char(ict_dt_mod,'yyyy/mm/dd') ICT_DT_MOD from c##sss_import.".$t;
	if (!empty($_REQUEST['find_eccezione'])){
		$f=implode("''",explode("'",strtoupper($_REQUEST['find_eccezione'])));
		$sql.=" where (upper(eczn_uid) like '%".$f."%' or upper(nome) like '%".$f."%' or upper(cognome) like '%".$f."%' or upper(nome || ' ' || cognome) like '%".$f."%' or upper(cognome || ' ' || nome) like '%".$f."%' or upper(cod_fisc) like '%".$f."%')
		union 
		select distinct LDAP_UID, GEST, COGNOME, NOME, COD_FISC, MAIL_ESTERNA, KA, DA, KR, DR, NOTA, case when trunc(dt_rap_ini) <= trunc(sysdate) and trunc(dt_rap_fin) >= trunc(sysdate) then 1 else 0 end attivo, to_char(dt_rap_fin,'YYYY/MM/DD') df, 0 ep, null ICT_USR_INS, null ICT_DT_INS, null ICT_USR_MOD, null ICT_DT_MOD from c##sss_import.ab_cn
		where cod_fisc not in (select distinct cod_fisc from c##sss_import.carriere_eccezioni)
		and (upper(ldap_uid) like '%".$f."%' or upper(nome) like '%".$f."%' or upper(cognome) like '%".$f."%' or upper(nome || ' ' || cognome) like '%".$f."%' or upper(cognome || ' ' || nome) like '%".$f."%' or upper(cod_fisc) like '%".$f."%')";
	}
	$sql.=" order by COGNOME, NOME";
	if (empty($_REQUEST['storico'])){$nt='lista_eccezioni';} else {$nt='lista_eccezioni_storico';}
	// GEST, K, ID_AB, NOME, COGNOME, COD_FISC, GENERE, DT_NASCITA, COMUNE, MAIL_ESTERNA, KA, DA, KR, DR, DTR, KI, DI, KAS, KS, DS, KU, DU, TR, DT_RAP_INI, DT_RAP_FIN, NOTA
	$a=load_db($conn_new,$sql,'o');
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		if (!empty($_REQUEST['find_eccezione'])){
			$g=[['LDAP_UID'],['GEST'],['COGNOME'],['NOME'],['COD_FISC'],['MAIL_ESTERNA'],['DA'],['DR'],['DF'],['NOTA'],['ATTIVO']];
			$d=['uid','gest','Cognome','Nome','Codice fiscale','Mail','Afferenza','Ruolo','Fine Rapporto','Nota','Attivo'];
		} else {
			$g=[['LDAP_UID'],['GEST'],['COGNOME'],['NOME'],['COD_FISC'],['MAIL_ESTERNA'],['DA'],['DR'],['DF'],['NOTA'],['ATTIVO'],['ICT_USR_INS','ICT_DT_INS','ICT_USR_MOD','ICT_DT_MOD']];
			$d=['uid','gest','Cognome','Nome','Codice fiscale','Mail','Afferenza','Ruolo','Fine Rapporto','Nota','Attivo','Ins mod'];
		}
		$s='<br />'.get_alert('Trovati '.$a[1].' record','info').get_table_data($a[2],$g,$d,$nt);
	} else {
		$s=get_alert('Nessuna eccezione trovata','danger');
	}
	return $s;	
}
function get_delete_eccezioni(){
	global $conn_new;
	if (isset($_REQUEST['conferma'])){
		$sql="delete from c##sss_import.carriere_eccezioni where cod_fisc='".trim($_REQUEST['cf'])."'";
		$a=load_db($conn_new,$sql,'o');
		$tit='<h2>Cancellazione confermata</h2>';
		$msg="L'eccezione per:<br /><strong>".$_REQUEST['nc']."</strong> (".trim($_REQUEST['cf']).") &egrave; stata cancellata !";
		$stl='bg-danger text-center text-white';
		$btn='';
		$domf='eczn_bottom';
		$domc=get_lista_eccezioni();
	} else {
		$tit='<h2>Cancella eccezione</h2>';
		$msg="Confermi la cancellazione dell'eccezione per:<br /><strong>".$_REQUEST['nc']."</strong> (".trim($_REQUEST['cf']).") ?";
		$stl='bg-danger text-center text-white';
		$btn='<button id="modal-ok" class="btn btn-success btn-block" data-dismiss="modal" cf="'.trim($_REQUEST['cf']).'" nc="'.$_REQUEST['nc'].'" act="de" dom="mm" conferma="y">Conferma cancellazione</button><button id="modal-ko" class="btn btn-dark btn-block" data-dismiss="modal">Annulla</button>';
		$domf='';
		$domc='';
	}
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn, 'domf' => $domf, 'domc' => $domc);
	return json_encode($r);		
}
function get_genere_nascita_comune(){
	if (empty($_REQUEST['eczn_cf'])){return;}
	$s='';
	$cf = new CheckCF();
	if ($cf->isFormallyCorrect($_REQUEST['eczn_cf'])) {
		$aa=$cf->getYearBirth();
		if ($aa > date('y')-15){$ce='19';} else {$ce='20';}
		$dt_nascita=$cf->getDayBirth().'/'.$cf->getMonthBirth().'/'.$ce.$cf->getYearBirth();
		$comune=strtoupper($cf->getCountryBirth());
		$genere=strtoupper($cf->getSex());
		
		// GENERE
		$s.='<div class="row bg-light">';
			$s.='<div class="col-sm-4 text-right">Genere:</div>';
			$s.='<div class="col-sm-8">';
				$ss='';
				$generi=array('M'=>'Maschio','F'=>'Femmina');
				$gk=array_keys($generi);
				$gv=array_values($generi);
				for ($j=0; $j < count($generi); $j++) {
					$ss.='<option data-tokens="'.$gk[$j].'" value="'.$gk[$j].'"';
					if ($gk[$j]==$genere){$ss.=' selected';}
					$ss.='>'.$gv[$j].'</option>';
				}
				$s.='<select name="eczn_genere" id="eczn_genere" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep >
					<option data-tokens="0" value="0"></option>
					'.$ss.'
				</select>';
			$s.='</div>';
		$s.='</div>';

		// DT_NASCITA
		$s.='<div class="row bg-light">';
			$s.='<div class="col-sm-4 text-right">Data di nascita:</div>';
			$s.='<div class="col-sm-3">';
				$s.='<input type="text" name="eczn_dtn" id="eczn_dtn" class="form-control form-control-sm" value="';
				$s.=$dt_nascita;
				$s.='" dto />';
			$s.='</div>';
		$s.='</div>';

		// COMUNE, 
		$s.='<div class="row bg-light">';
			$s.='<div class="col-sm-4 text-right">Comune o stato estero:</div>';
			$s.='<div class="col-sm-8">';
				$ss='';
				if (!empty($_SESSION['IAM']['lista_comuni'])){
					for ($j=0; $j < count($_SESSION['IAM']['lista_comuni']['K']); $j++) {
						$ss.='<option data-tokens="'.$_SESSION['IAM']['lista_comuni']['K'][$j].'" value="'.$_SESSION['IAM']['lista_comuni']['K'][$j].'"';
						if ($_SESSION['IAM']['lista_comuni']['K'][$j]==$comune){$ss.=' selected';}
						$ss.='>'.$_SESSION['IAM']['lista_comuni']['D'][$j].'</option>';
					}
				}
				$s.='<select name="eczn_comune" id="eczn_comune" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep >
					<option data-tokens="0" value="0"></option>
					'.$ss.'
				</select>';
			$s.='</div>';
		$s.='</div>';		
	}

	return $s;
}
// --- password ---
function get_password_disallineate(){		// cerca le password disallineate sugli account attivi
	global $ldap_conn, $conn_sl;
	$s='<br />'.get_alert('password disallineate o anagrafiche mancanti nello storico');
	$s.='<ol>';
	if (!empty($_SESSION['IAM']['ab_can'])){
		for ($i=0; $i < count($_SESSION['IAM']['ab_can']['LDAP_UID']); $i++) {
			if ($_SESSION['IAM']['ab_can']['LDAP_UID'][$i] != ''){
				$uid=$_SESSION['IAM']['ab_can']['LDAP_UID'][$i];
				$m='';
				$pp='';
				try {
					$sql="select * from storico_ldap.richieste_account where ra_uid = '".$uid."' or ra_aliases like '%".$uid."%'";
					$a=mysqli_query($conn_sl,$sql);
					if (!empty(mysqli_error($conn_sl))){
						$m.=get_alert($sql.'<br />'.mysqli_error($conn_sl),'danger');
					} else {
						if (mysqli_num_rows($a) > 0) {	// dati trovati
							// ho il dato nello storico
							$j=array();
							foreach($a as $row){$j[]=$row;}
							if (count($j)==1){
								$pp=$j[0]['ra_prima_password'];
							} else {
								// $m.=get_alert('Ho trovato pi&ugrave; di un record per '.$uid,'danger',true);
								foreach($j as $row){
									if ($row['ra_uid']==$uid){
										$pp=$j[0]['ra_prima_password'];
									} else {
										$aa=explode('|',$row['ra_aliases']);
										foreach ($aa as $aai){
											if ($aai==$uid){$pp=$row['ra_prima_password'];}
										}
									}
									// $m.=get_alert($row['ra_k'].' '.$row['ra_uid'].' '.$row['ra_nome'].' '.$row['ra_cognome'],'warning',true);
								}
							}
						} else {
							// non l'ho trovato nello storico
							$m.=get_alert('Anagrafica non mappata nello storico','danger',true);
						}
					}
				} catch (Exception $ee) {
					$m=get_alert('ERRORE: '.$ee,'danger',true);
				}
				if ($pp!=''){
					$o_ldap=ldap_authenticate($uid,$pp,'LDAP');
					$o_ad=ldap_authenticate($uid,$pp,'AD');
					if ($o_ad['autenticato']==0){
						$o_ad=ldap_authenticate($uid,'dsf5672SE!2017','AD');
					}

					if (($o_ldap['esiste']==1 and $o_ad['esiste']==0) or ($o_ldap['esiste']==0 and $o_ad['esiste']==1)
						) {
							// --- STATO INCONGRUO - esiste da una parte e non dall'altra
							if ($o_ldap['esiste']==0){$manca=' LDAP';}
							if ($o_ad['esiste']==0){$manca=' AD';}
							if ($o_ldap['esiste']==1){$esiste=' LDAP';}
							if ($o_ad['esiste']==1){$esiste=' AD';}
							$m.=get_alert('STATO INCONGRUO - <strong>esiste in '.$esiste.' ma non esiste in '.$manca.'</strong>','danger',true);
					} else {
						if (($o_ldap['autenticato']==1 and $o_ad['autenticato']==0) or ($o_ldap['autenticato']==0 and $o_ad['autenticato']==1)
							) {
								// --- STATO INCONGRUO - le password non sono allineate
								$m.=get_alert('STATO INCONGRUO - <strong>password non allineate</strong>','danger',true);
						}
					}
					
				}
				if ($m!=''){
					$s.='<li><strong>'.$_SESSION['IAM']['ab_can']['NOME'][$i].' '.$_SESSION['IAM']['ab_can']['COGNOME'][$i].'</strong>';
					$s.='<br /><span class="text-primary">'.$_SESSION['IAM']['ab_can']['LDAP_UID'][$i].'</span>  - '.$_SESSION['IAM']['ab_can']['DR'][$i].'  - '.$_SESSION['IAM']['ab_can']['DA'][$i];
					$s.='<br />'.$m;
				}
			}
		}	
	}
	$s.='</ol>';
	return $s;
}
function get_cambia_password(){					// form
	global $conn_new, $ldap_conn;
	$t=$_REQUEST['tipo'];
	$s='<br />';
	$s.='<form id="f_gp" class="">';
		
		$s.='<div class="row alert alert-dark">';
			$s.='<div class="col-sm-8">';
				$s.='<div class="row alert alert-secondary">';
					if ($t=='cp'){$l='CAMBIA PASSWORD';}
					if ($t=='vp'){$l='VERIFICA PASSWORD';}
					if ($t=='fp'){$l='FORZA PASSWORD';}
					$s.='<div class="col-sm-12 text-center"><strong>'.$l.'</strong></div>';
				$s.='</div>';
				$s.='<div class="row bg-light py-1">';
					$s.='<div class="col-sm-4 text-right">uid:</div>';
					$s.='<div class="col-sm-8">';
						$s.='<input type="text" name="gp_uid" id="gp_uid" class="form-control form-control-sm" value="" />';
					$s.='</div>';
				$s.='</div>';
				if ($t=='cp'){$l='Vecchia password';}
				if ($t=='vp'){$l='Password:';}
				if ($t=='cp' or $t=='vp'){
					$s.='<div class="row bg-light py-1">';
						$s.='<div class="col-sm-4 text-right">'.$l.'</div>';
						$s.='<div class="col-sm-8">';
							$s.='<input type="text" name="gp_old" id="gp_old" class="form-control form-control-sm" value="" />';
						$s.='</div>';
					$s.='</div>';
				}
				if ($t=='cp' or $t=='fp'){
					$s.='<div class="row bg-light py-1">';
						$s.='<div class="col-sm-4 text-right">Nuova password:</div>';
						$s.='<div class="col-sm-8">';
							$s.='<input type="text" name="gp_np1" id="gp_np1" class="form-control form-control-sm" value="" />';
						$s.='</div>';
					$s.='</div>';
				}
				if ($t=='cp'){				
					$s.='<div class="row bg-light py-1">';
						$s.='<div class="col-sm-4 text-right">Ripeti nuova password:</div>';
						$s.='<div class="col-sm-8">';
							$s.='<input type="text" name="gp_np2" id="gp_np2" class="form-control form-control-sm" value="" />';
						$s.='</div>';
					$s.='</div>';
				}
				
				if ($t=='cp' or $t=='fp'){
					$s.='<div class="row alert alert-dark">';
						$s.='<div class="col-sm-12 text-center">';
							
							$s.='<p class="text-danger">La tua nuova password deve essere di ALMENO 8 caratteri, di cui almeno 3 devono essere numeri o simboli.';
							$s.='<br /><span class="text-primary">Your new password must be 8 characters long or longer and have at least: 3 numbers or symbols.</p>';
							
							$s.='<p class="text-danger">Le password possono contenere:caratteri maiuscoli, minuscoli, simboli e numeri, facendo distinzione tra maiuscole e minuscole.';
							$s.='<br /><span class="text-primary">Passwords can contain: uppercase letters, tiny characters, symbols and numbers. Be careful to distinguish small from uppercase letters.</span></p>';
							
							$s.='<p class="text-danger">Puoi anche utilizzare i seguenti simboli:';
							$s.='<br /><span class="text-primary">You can also use these symbols:</span></span>';
							// $s.='<br /><span class="text-danger"><h3>! ? = % * . : ~ + -</h3></span></p>';
							$s.='<br /><h3>! ? = % * . : + -</h3></p>';
							
							$s.='<p class="text-danger">Devi inoltre immettere una nuova password e NON riutilizzare la password corrente o quella precedente.';
							$s.='<br /><span class="text-primary">You must use a new password, your new password can not be the same as your current password or previuos password.</span></p>';
							
						$s.='</div>';
					$s.='</div>';
				}
				
			$s.='</div>';
			$s.='<div class="col-sm-4">';
				$s.='<div class="container-fluid">';
					$s.='<div class="row">';
						$s.='<div class="col-sm-6">';
							if ($t=='cp'){$l='Cambia';}
							if ($t=='fp'){$l='Forza';}
							if ($t=='vp'){$l='Verifica';}
							$s.='<button class="btn btn-success btn-sm btn-block" act="sgp" dom="sgp" tab="gp" tipo="'.$l.'">'.$l.'</button>';
						$s.='</div>';
					$s.='</div>';
					$s.='<div class="row"><div class="col-sm-12" id="sgp"></div></div>';
				$s.='</div>';
			$s.='</div>';

	$s.='</form>';

/*
	Servizi ICT
Hai bisogno di aiuto?/Do you need help? helpdesk
PASSWORD SCADUTA? / PASSWORD EXPIRED?
Cambio Password / Password Change
La tua nuova password deve essere di ALMENO 8 caratteri,
di cui almeno 3 devono essere numeri o simboli.
Le password possono contenere:caratteri maiuscoli, minuscoli, simboli e numeri, facendo distinzione tra maiuscole e minuscole.
Your new password must be 8 characters long or longer and have at least:
3 numbers or symbols.
Passwords can contain: uppercase letters, tiny characters, symbols and numbers.
Be careful to distinguish small from uppercase letters.

Puoi anche utilizzare i seguenti simboli:
You can also use these symbols:
! ? = % * . : ~ + -

Devi inoltre immettere una nuova password e NON riutilizzare la password corrente o quella precedente.
You must use a new password, your new password can not be the same as your current password or previuos password.

Nome utente
Username:	
n.cognome / n.surname
Password corrente
Current password:	
Nuova password
New password:	
Conferma nuova password
New password (again):	
 Cancella / Cancel
Questa pagina è stata autorizzata dal Responsabile Servizi ICT della Scuola Sant'Anna.
This page has been authorized by Chief of Information Officers of Sant'Anna School
PGP Public Key

*/	
	return $s;
}
function get_salva_modifica_password(){ // elaborazione
	global $ldap_conn, $ad_conn, $conn_sl, $devel;

	$nome='account';
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}		// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}		// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}		// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}		// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict

	$s='<br />';
	$t=$_REQUEST['tipo'];
	$e=''; $w='';
	if (empty($_REQUEST['gp_uid'])){
		$e.='<div class="row"><div class="col-sm-12 alert alert-danger">Manca <strong>Uid</strong></div></div>';
	}
	if ($t=='Cambia'){
		if (empty($_REQUEST['gp_old'])){
			$e.='<div class="row"><div class="col-sm-12 alert alert-danger">Manca <strong>Vecchia Password</strong></div></div>';
		}
	}
	if ($t=='Verifica'){
		if (empty($_REQUEST['gp_old'])){
			$e.='<div class="row"><div class="col-sm-12 alert alert-danger">Manca <strong>Password</strong></div></div>';
		}
	}
	if ($t=='Cambia' or $t=='Forza'){
		if (empty($_REQUEST['gp_np1'])){
			$e.='<div class="row"><div class="col-sm-12 alert alert-danger">Manca <strong>Nuova Password</strong></div></div>';
		}
	}
	if ($t=='Cambia'){
		if (empty($_REQUEST['gp_np2'])){
			$e.='<div class="row"><div class="col-sm-12 alert alert-danger">Manca <strong>Ripetizione Nuova Password</strong></div></div>';
		}
		if (!empty($_REQUEST['gp_np1']) and !empty($_REQUEST['gp_np2'])){
			if ($_REQUEST['gp_np1'] != $_REQUEST['gp_np2']){
				$e.='<div class="row"><div class="col-sm-12 alert alert-danger"> <strong>Nuova Password</strong> e <strong>Ripetizione Nuova Password</strong> sono diverse</div></div>';
			}
		}
		if (!empty($_REQUEST['gp_np1']) and !empty($_REQUEST['gp_old'])){
			if ($_REQUEST['gp_np1'] == $_REQUEST['gp_old']){
				$e.='<div class="row"><div class="col-sm-12 alert alert-danger"> <strong>Vecchia Password</strong> e <strong>Nuova Password</strong> sono uguali</div></div>';
			}
		}
	}

	// cerca il record di storico_ldap
	if ($t=='Cambia' or $t=='Forza'){
		$y=array();
		$x=array();
		$ra_k=0;
		$sql="select * from storico_ldap.richieste_account where ra_uid = '".$_REQUEST['gp_uid']."' or ra_aliases like '%".$_REQUEST['gp_uid']."%'";
		$a=mysqli_query($conn_sl,$sql);						
		if (!empty(mysqli_error($conn_sl))){
			$e.=mysqli_error($conn_sl);
		} else {
			foreach($a as $row){$y[] = $row;}
			if (count($y) > 0){
				foreach ($y as $row){
					if ($row['ra_uid']==$_REQUEST['gp_uid']){
						$x[]=$row;
					} else {
						if ($row['ra_aliases']!=''){
							$aa=explode('|',$row['ra_aliases']);
							foreach($aa as $aai){
								if ($aai==$_REQUEST['gp_uid']){$x[]=$row;}
							}
						}
					}
				}
			}
		}
		if (empty($x)){
			$e.=get_alert('utente non trovato','danger');
		}
		if (count($x) > 1){
			$e.=get_alert('utente non identificato nello storico','danger');
			if ($su or $ict){
				foreach ($x as $xi){
					$e.=get_alert('uid: '.$xi['ra_uid'].' - aliases: '.$xi['ra_aliases']);
				}
			}
		}
	}
	
	if ($e!=''){
		$s.=$e;
		return $s;
	}

	$s.=$w;
	
	if ($_REQUEST['tipo']=='Verifica'){
		// if ($devel){
			// $uid='im_'.$uid;
		// }
		$m='';
		$o=ldap_authenticate($_REQUEST['gp_uid'],$_REQUEST['gp_old'],'LDAP');
		if($o['autenticato']){
			$m.=get_alert('<strong>Ok!</strong> Autenticato correttamente su LDAP','success');
		} else {
			$mm='<strong>Ko!</strong> Autenticazione su LDAP fallita';
			if ($su or $ict){
				if(!empty($o['err'])){$mm.='<br />'.$o['err'];}
				if (!$o['esiste']){$mm.='<br />Account inesistente in LDAP';}
			}
			$m.=get_alert($mm,'danger');
		}
		if ($su or $ict){
			$o=ldap_authenticate($_REQUEST['gp_uid'],$_REQUEST['gp_old'],'LDAP_GUEST');
			if($o['autenticato']){
				$m.=get_alert('<strong>Ok!</strong> Autenticato correttamente su LDAP GUEST','success');
			} else {
				$mm='<strong>Ko!</strong> Autenticazione su LDAP GUEST fallita';
				if ($su or $ict){
					if(!empty($o['err'])){$mm.='<br />'.$o['err'];}
					if (!$o['esiste']){$mm.='<br />Account inesistente in LDAP GUEST';}
				}
				$m.=get_alert($mm,'danger');
			}
		}
		$o=ldap_authenticate($_REQUEST['gp_uid'],$_REQUEST['gp_old'],'AD');
		if($o['autenticato']){
			$m.=get_alert('<strong>Ok!</strong> Autenticato correttamente su AD','success');
		} else {
			$mm='<strong>Ko!</strong> Autenticazione su AD fallita';
			if ($su or $ict){
				if(!empty($o['err'])){$mm.='<br />'.$o['err'];}
				if (!$o['esiste']){$mm.='<br />Account inesistente in AD';}
			}
			$m.=get_alert($mm,'danger');
		}
		$s.=$m;
	}
	
	if ($_REQUEST['tipo']=='Forza'){
		// riporto lo stato a completata
		// cancello la nuova password (potrei lasciarla tanto viene elaborata solo se lo stato è aspetta_ad_cgp)
		// controllo esistenza dell'account
		$oldap=ldap_authenticate($_REQUEST['gp_uid'],$_REQUEST['gp_np1'],'LDAP');
		$oad=ldap_authenticate($_REQUEST['gp_uid'],$_REQUEST['gp_np1'],'AD');
		if ($oldap['esiste']==0){$e.=get_alert('Inesistente in LDAP','danger');}
		if ($oad['esiste']==0){$e.=get_alert('Inesistente in AD','danger');}
		// if ($oldap['autenticato']){$e.=get_alert('Password = attuale in LDAP','danger');}
		// if ($oad['autenticato']){$e.=get_alert('Password = attuale in AD','danger');}
		if ($e==''){
			$sql="update storico_ldap.richieste_account set ra_stato = 'aspetta_ad_cgp', ra_uid_modifica_password = '".$_REQUEST['gp_uid']."', ra_modifica_password = '".$_REQUEST['gp_np1']."' where ra_uid = '".$x[0]['ra_uid']."'";
			$s.=get_alert('Forza - '.$sql);
			$a=mysqli_query($conn_sl,$sql);						
			if (!empty(mysqli_error($conn_sl))){
				$e.=mysqli_error($conn_sl);
			} else {
				$s.=get_alert('Il cambio password sarà effettuato entro 10 minuti<br />Ricarica il tab Account per verificarne il risultato','warning');
				$s.=set_ra_log($ra_k,"cgpAD - Password in modifica - aspetta AD",'aspetta_ad_cgp'); // scrivo il log						
			}
		}
	}	
	
	if ($_REQUEST['tipo']=='Cambia'){
		// controllo la vecchia password su LDAP e AD
		$oldap=ldap_authenticate($_REQUEST['gp_uid'],$_REQUEST['gp_old'],'LDAP');
		$oad=ldap_authenticate($_REQUEST['gp_uid'],$_REQUEST['gp_old'],'AD');
		if ($oldap['esiste']==0){$e.=get_alert('Inesistente in LDAP','danger');}
		if ($oad['esiste']==0){$e.=get_alert('Inesistente in AD','danger');}
		if($oldap['autenticato'] and $oad['autenticato']){
			$s.=get_alert('<strong>Ok!</strong> Vecchia password corretta','success');
			$sql="update storico_ldap.richieste_account set ra_stato = 'aspetta_ad_cgp', ra_uid_modifica_password = '".$_REQUEST['gp_uid']."', ra_modifica_password = '".$_REQUEST['gp_np1']."' where ra_uid = '".$x[0]['ra_uid']."'";
			$a=mysqli_query($conn_sl,$sql);						
			if (!empty(mysqli_error($conn_sl))){
				$e.=mysqli_error($conn_sl);
			} else {
				$s.=get_alert('Il cambio password sar&agrave; effettuato entro 10 minuti<br />Ricarica il tab Account per verificarne il risultato','warning');
				$s.=set_ra_log($ra_k,"cgpAD - Password in modifica - aspetta AD",'aspetta_ad_cgp'); // scrivo il log						
			}
		} else {
			$e.=get_alert('<strong>Ko!</strong> Vecchia password errata','danger');
			if ($su or $ict){
				if ($oldap['esiste']){
					$e.=get_alert($oldap['err']);
				} else {
					$e.=get_alert('Non trovato in LDAP');
				}
				if ($oad['esiste']){
					$e.=get_alert($oad['err']);
				} else {
					$e.=get_alert('Non trovato in AD');
				}
			}
		}
	}

	if ($e!=''){
		$s.=$e;
	}
	return $s;
}
// --- foto ---
function get_form_upload_foto(){
	$s='<div class="row py-2">';
		$s.='<div class="col-sm-6 alert alert-warning py-2">';
			$s.='<div class="row py-1">';
				$s.='<div class="col-sm-12 text-center">';
					$s.='<a class="btn btn-sm btn-block file-btn">Carica foto: <input type="file" id="upload-foto" value="Choose a file" accept="image/*" /></a>';
				$s.='</div>';
			$s.='</div>';
			$s.='<div class="row py-1">';
				$s.='<div class="col-sm-2 text-right"><strong>Foto per:</strong></div>';
				$s.='<div class="col-sm-7">';
					if (!empty($_SESSION['IAM']['ab_can'])){
						$ss='';
						for ($i=0; $i < count($_SESSION['IAM']['ab_can']['LDAP_UID']); $i++) {
							if ($_SESSION['IAM']['ab_can']['LDAP_UID'][$i] != ''){
								$ss.='<option data-tokens="'.$_SESSION['IAM']['ab_can']['LDAP_UID'][$i].'" value="'.$_SESSION['IAM']['ab_can']['LDAP_UID'][$i].'"';
								if (!empty($_REQUEST['uid_fm'])){if ($_REQUEST['uid_fm']==$_SESSION['IAM']['ab_can']['LDAP_UID'][$i]){
									$ss.=' selected';
								}}
								$ss.='>'.$_SESSION['IAM']['ab_can']['COGNOME'][$i].' '.$_SESSION['IAM']['ab_can']['NOME'][$i].'</option>';
							}
						}
						$s.='<select name="uii" id="uii" class="form-control form-control-sm" value="" data-live-search="true" data-size="8" sep >
							<option data-tokens="0" value="0"></option>
							'.$ss.'
						</select>';
					} else {
						$s.='<input id="uii" placeholder="uid" type="text" class="form-control" />';
					}
				$s.='</div>';
				$s.='<div class="col-sm-3">';
					$s.='<button class="btn btn-success btn-sm btn-block" act="get_can_d" getf="uii" dom="foto_can_detail">vedi dettaglio</button>';
				$s.='</div>';
			$s.='</div>';
			$s.='<div class="row py-1">';
				$s.='<div class="col-sm-12">';
					$s.='<div id="foto_can_detail">';
						if (!empty($_REQUEST['uid_fm'])){
							$s.=get_can_d($_REQUEST['uid_fm']);
						}
					$s.='</div>';
				$s.='</div>';
			$s.='</div>';
			$s.='<div class="row py-1">';
				$s.='<div class="col-sm-12 text-center">';
					$s.='<button class="btn btn-info btn-sm btn-block upload-result" tipo="foto">Sostituzisci foto</button>';
				$s.='</div>';
			$s.='</div>';
		$s.='</div>';
		$s.='<div class="col-sm-6 alert alert-dark text-center py-2">';
			$s.='<div style="width: 300px; height: 450px; margin: 0 auto;">';
				$s.='<div id="upload-box"></div>';
			$s.='</div>';
		$s.='</div>';
	$s.='</div>';
	return $s;
}
function get_table_richieste_foto(){
	$s='';
	$dir='/var/www/html/rac/img/richieste';
	$a = array_diff(scandir($dir), array('..', '.'));		// tolgo . e ..
	foreach($a as $f){
		if (!is_dir($dir.'/'.$f)){			// non è una dir
			$ff=$dir.'/'.$f;
			$i = pathinfo($ff);
			if (strtolower($i['extension'])=='json') {	
				tolog('lettura del file: '.$ff);
				if ($s==''){$s.='<table class="table table-striped table-sm table-responsive"><thead><tr><th>uid</th><th>data</th><th>ip</th><th>richiesta</th><th>foto</th><th>commento</th><th>azioni</th></tr></thead><tbody>';} // intesta tabella
				$s.='<tr>';
				$myfile = fopen($ff, "r");
				$r=fread($myfile,filesize($ff));
				$richiesta=json_decode($r);
				fclose($myfile);
				$imgpop="<img class='img-fluid' src='".$richiesta->{'img'}."' />";
				$s.='<td>'.$richiesta->{'ldap_uid'}.'</td>
					<td>'.$richiesta->{'data'}.'</td>
					<td>'.$richiesta->{'ip'}.'</td>
					<td>'.$richiesta->{'tipo'}.'</td>
					<td><a data-toggle="popover" data-trigger="hover" data-html="true" title="foto della richiesta" data-content="'.$imgpop.'"><img class="img-fluid logo" src="'.$richiesta->{'img'}.'" /></a></td>
					<td><input id="cr_'.implode("_",explode(".",$richiesta->{'ldap_uid'})).'" type="text" class="form-control" /></td>
					<td><button class="btn btn-success btn-sm btn-block" act="rr_foto" tipo="a" uid="'.$richiesta->{'ldap_uid'}.'" getf="cr_'.implode("_",explode(".",$richiesta->{'ldap_uid'})).'">Approva</button>
					<button class="btn btn-danger btn-sm btn-block" act="rr_foto" tipo="r" uid="'.$richiesta->{'ldap_uid'}.'" getf="cr_'.implode("_",explode(".",$richiesta->{'ldap_uid'})).'">Rifiuta</button></td>
				';
				$s.='</tr>';
			}								
		}
	}	
	if ($s!=''){
		$s.='</tbody></table>';
	} else {
		$s=get_alert('Nessuna richiesta da elaborare','danger');
	}
	return $s;
}
function get_risposta_richiesta_foto(){
	$uid=$_REQUEST['uid'];
	$tipo=$_REQUEST['tipo'];
	$id_commento='cr_'.implode("_",explode(".",$uid));
	$comm=$_REQUEST[$id_commento];
	if (!in_array($tipo, array('a','r'))){return;} // a=approvato, r=rifiutato
	$s='';
	$dir='/var/www/html/rac/';
	$f=$dir.'img/richieste/'.$uid.'.json';
	if (file_exists($f)){
		$myfile = fopen($f, "r");
		$r=fread($myfile,filesize($f));
		$richiesta=json_decode($r);
		fclose($myfile);
  	tolog('stati: '.print_r($richiesta, true));
		// $a=array();
		$a = new stdClass();
		switch ($tipo) {
			case 'a':	// richiesta approvata
				if ($richiesta->{'tipo'}=='foto'){
					$fjpg=$dir.'img/'.$richiesta->{'ldap_uid'}.'.jpg';
					// se esiste muovo rinominandola la vecchia foto
					if (file_exists($fjpg)){	
						$foldjpg=$dir.'img/oldimg/'.$richiesta->{'ldap_uid'}.'_'.date("YmdHis").'.jpg';
						rename($fjpg, $foldjpg);	
					}
					// aggiorno la richiesta
					$a->stato='approvata';
					$a->dataora=date("d/m/Y H:i:s");
					$a->uid=$uid;
					$a->ip=getIpAddress();
					$a->commento=$comm;
					$richiesta->stati[]=$a; 
					$r=json_encode($richiesta);
					$myfile = fopen($f, "w");
					fwrite($myfile, $r);
					fclose($myfile);
					// muovo la richiesta in evase
					rename($f, $dir.'img/evase/'.$uid.'_'.date("YW").'.json');	// anno e settimana dell'anno
					// creo la nuova immagine
					$data=explode(',',$richiesta->{'img'});
					$ifp=fopen($fjpg,'wb'); 
					fwrite($ifp,base64_decode($data[1]));
					fclose($ifp); 
					// file_put_contents($fjpg, base64_decode($richiesta->{'img'}));
					// comunico il risultato
					return '<h2>sostituzione foto approvata</h2>uid:'.$uid.'<br>commento: '.$comm;
				} else {
					// aggiorno la richiesta
					$a->stato='stampa richiesta';
					$a->dataora=date("d/m/Y H:i:s");
					
					if ($_SESSION['IAM']['myACL']['ACL_PROFILO'][0]==9){					
						$a->uid=$uid;
					} else {
						$a->uid=$_SESSION['IAM']['uid_login'];
					}

					$a->ip=getIpAddress();
					$a->commento=$comm;
					$richiesta->stati[]=$a; 
					$r=json_encode($richiesta);
					$myfile = fopen($f, "w");
					fwrite($myfile, $r);
					fclose($myfile);
					// muovo la richiesta in badge
					rename($f, $dir.'img/badge/'.$uid.'.json');
					// comunico il risultato
					return '<h2>stampa nuovo badge richiesta</h2>uid:'.$uid.'<br>commento: '.$comm;
				}
				break;
			case 'r':	// richiesta rifiutata
				if ($richiesta->{'tipo'}=='foto'){
					// aggiorno la richiesta
					$a->stato='richiesta sostituzione foto rifiutata';
					$a->dataora=date("d/m/Y H:i:s");
					$a->uid=$uid;
					$a->ip=getIpAddress();
					$a->commento=$comm;
					$richiesta->stati[]=$a; 
					$r=json_encode($richiesta);
					$myfile = fopen($f, "w");
					fwrite($myfile, $r);
					fclose($myfile);
					// muovo la richiesta in evase
					rename($f, $dir.'img/evase/'.$uid.'_'.date("YW").'.json');	
					// comunico il risultato
					return '<h2>richiesta sostituzione foto rifiutata</h2>uid:'.$uid.'<br>commento: '.$comm;
				} else {
					// aggiorno la richiesta
					$a->stato='richiesta stampa nuovo badge rifiutata';
					$a->dataora=date("d/m/Y H:i:s");
					$a->uid=$uid;
					$a->ip=getIpAddress();
					$a->commento=$comm;
					$richiesta->stati[]=$a; 
					$r=json_encode($richiesta);
					$myfile = fopen($f, "w");
					fwrite($myfile, $r);
					fclose($myfile);
					// muovo la richiesta in evase
					rename($f, $dir.'img/evase/'.$uid.'_'.date("YW").'.json');	
					// comunico il risultato
					return '<h2>richiesta stampa nuovo badge rifiutata</h2>uid:'.$uid.'<br>commento: '.$comm;
				}
				break;
		}		
	}
}
function get_info_richiesta_foto(){
	$uid=$_REQUEST['uii'];	// $uid=$_REQUEST['uid'];
	$f=''; $s='';
	$dir='/var/www/html/rac/';
	
	if (file_exists($dir.'img/richieste/'.$uid.'.json')){
		$f=$dir.'img/richieste/'.$_SESSION['IAM']['uid_login'].'.json';
		$t='Dati richiesta pendente:';
	}
	if (file_exists($dir.'img/badge/'.$uid.'.json')){
		$f=$dir.'img/badge/'.$_SESSION['IAM']['uid_login'].'.json';
		$t='Dati richiesta stampa pendente:';
	}
	if (file_exists($dir.'img/evase/'.$uid.'_'.date("YW").'.json')){
		$f=$dir.'img/evase/'.$uid.'_'.date("YW").'.json';
		$t='Dati richiesta evasa:';
	}
	if ($f!=''){
		$myfile = fopen($f, "r");
		$r=fread($myfile,filesize($f));
		$richiesta=json_decode($r);
		fclose($myfile);
		$s='<div class="row">
				<div class="col-sm-3 text-center">
					<strong>'.$t.'</strong>
					<br>uid: '.$richiesta->{'ldap_uid'}.'
					<br>data: '.$richiesta->{'data'}.'
					<br>ip: '.$richiesta->{'ip'}.'
					<br>sostituzione di: '.$richiesta->{'tipo'}.'
				</div>
				<div class="col-sm-3 text-center">
					<img src="'.$richiesta->{'img'}.'" />
				</div>
				<div class="col-sm-6 text-center">';
		$t='';
		foreach($richiesta->{'stati'} as $row){
			if ($t==''){$t.='<table class="table table-striped table-sm table-responsive"><thead><tr><th>uid</th><th>data</th><th>ip</th><th>stato</th><th>commento</th></tr></thead><tbody>';}
			$t.='<tr>';
				$t.='<td>'.$row->{'uid'}.'</td>
					<td>'.$row->{'dataora'}.'</td>
					<td>'.$row->{'ip'}.'</td>
					<td>'.$row->{'stato'}.'</td>
					<td>'.$row->{'commento'}.'</td>
				';
			$s.='</tr>';
		}								
		if ($t!=''){$t.='</tbody></table>';}

		$s.=$t.'</div>';
		$s.='</div>';
	}
	return $s;
}
function set_upload_foto(){
	$f64=$_REQUEST['f64'];
	$tipo=$_REQUEST['tipo'];
	$uid=$_REQUEST['uii']; // $uid=$_REQUEST['uid'];
	$dir='/var/www/html/rac/';

	$s='';
	$f=$dir.'img/richieste/'.$uid.'.json';
	if (file_exists($f)){
		$s.='<div class="row">
					<div class="col-sm-4">
						'.$uid.'
						<br>La sua richiesta di sostituzione '.$tipo.' <strong>non &egrave; stata inoltrata</strong> in quanto risulta una richiesta precedente non ancora eleborata
					</div>';
		$myfile = fopen($f, "r");
		$r=fread($myfile,filesize($f));
		$richiesta=json_decode($r);
		fclose($myfile);
		$s.='<div class="col-sm-8">
						'.get_info_richiesta_foto($uid).'
				</div>
		</div>';
	} else {
		$s.=$uid;
		$s.='<br>La sua richiesta di sostituzione '.$tipo.' <strong>&egrave; stata inoltrata</strong>';
		$s.='<br><br><img src="'.$f64.'" />';
		$richiesta=array();
		$richiesta['ldap_uid']=$uid;
		$richiesta['data']=date("d/m/Y");
		$richiesta['ip']=getIpAddress();
		$richiesta['template']='170044';	// se afferisce ad un istituto chiedere se vuole il badge personalizzato
		$richiesta['tipo']=$tipo;					// foto / badge
		$richiesta['stati']=array();
		$a = new stdClass();
		$a->stato='richiesta';
		$a->dataora=date("d/m/Y H:i:s");
		$a->uid=$uid;
		$a->ip=getIpAddress();
		$a->commento='';
		$richiesta['stati'][0]=$a;
		// $richiesta->stati[]=$a; 
		$richiesta['img']=$f64;						// immagine codificata in base64
		$r=json_encode($richiesta);
		$myfile = fopen($f, "w");
		fwrite($myfile, $r);
		fclose($myfile);
	}
	return $s;
}
function get_rfm_foto(){
//	global $conn_new;
	$s='';
//	$aauid=array();
//	$dir='/var/www/html/rac/img';
//	$a = array_diff(scandir($dir), array('..', '.'));		// tolgo . e ..
//	foreach($a as $f){
//		if (!is_dir($dir.'/'.$f)){			// non è una dir
//			$ff=$dir.'/'.$f;
//			$i = pathinfo($ff);
//			if (strtolower($i['extension'])=='jpg') {	
//				$auid[] = $i['filename'];
//			}								
//		}
//	}	
//	$n=count($auid);
//	if ($n > 0){

		$nt='tb-foto_mancanti';
		$t='<table id="'.$nt.'" class="table table-striped table-sm table-responsive"><thead><tr><th></th><th>uid</th><th>mail</th><th>cognome e nome</th><th>ruolo</th><th>afferenza</th><th>inquadramento</th><th>inizio</th><th>scadenza</th></tr></thead><tbody>';
		$n=0;
		$n1=0;
		for($i=0; $i<count($_SESSION['IAM']['ab_can']['LDAP_UID']); $i++){
			if ($_SESSION['IAM']['ab_can']['LDAP_UID'][$i] != ''){
				$ff='';
				try {
// esempi:
// https://dotnetu.local/ms/getfoto.php?toc===AW55EVNdEeBNmaF1zIxIHQsBzU&cf=BNGLRT57T20G702B
// https://dotnetu.local/ms/getfoto.php?toc===AW55EVNdEeBNmaF1zIxIHQsBzU&cf=BNGLRT57T20G702B&outt=html
// https://dotnetu.local/ms/getfoto.php?toc===AW55EVNdEeBNmaF1zIxIHQsBzU&uid=a.bongiorni&outt=html
// https://dotnetu.local/ms/getfoto.php?tic===AW55EVNdEeBNmaF1zIxIHQsBzU&cf=BNGLRT57T20G702B	// verbo errato

// https://dotnetu.local/ms/getfoto.php?toc===AW55EVNdEeBNmaF1zIxIHQsBzU&uid=l.viegi&outt=html


					$ff = file_get_contents("https://dotnetu.local/ms/getfoto.php?toc===AW55EVNdEeBNmaF1zIxIHQsBzU&uid=".$_SESSION['IAM']['ab_can']['LDAP_UID'][$i]);
				} catch(Exception $e) {
					// $errore.='<br />'.$e->getMessage();
					$ff='';
				}
				// if ($_SESSION['IAM']['ab_can']['LDAP_UID'][$i] != ''){if (!in_array($_SESSION['IAM']['ab_can']['LDAP_UID'][$i],$auid)){
				if ($ff==''){
					$n1++;
					$t.='<tr>';
						$t.='<td><button title="Carica una nuova foto" class="btn btn-success btn-sm btn-block" act="cn_foto" dom="gf_bottom" uid_fm="'.$_SESSION['IAM']['ab_can']['LDAP_UID'][$i].'">Carica</button></td>';
						$d=$_SESSION['IAM']['ab_can']['DT_RAP_FIN'][$i];
						$df=substr($d,6,4).'/'.substr($d,3,2).'/'.substr($d,0,2);
						$d=$_SESSION['IAM']['ab_can']['DT_RAP_INI'][$i];
						$di=substr($d,6,4).'/'.substr($d,3,2).'/'.substr($d,0,2);
						$t.='<td>'.$_SESSION['IAM']['ab_can']['LDAP_UID'][$i].'</td>
							<td>'.$_SESSION['IAM']['ab_can']['MAIL'][$i].'</td>
							<td>'.$_SESSION['IAM']['ab_can']['COGNOME'][$i].' '.$_SESSION['IAM']['ab_can']['NOME'][$i].'</td>
							<td>'.$_SESSION['IAM']['ab_can']['KR'][$i].' '.$_SESSION['IAM']['ab_can']['DR'][$i].'</td>
							<td>'.$_SESSION['IAM']['ab_can']['KA'][$i].' '.$_SESSION['IAM']['ab_can']['DA'][$i].'</td>
							<td>'.$_SESSION['IAM']['ab_can']['KI'][$i].' '.$_SESSION['IAM']['ab_can']['DI'][$i].'</td>
							<td>'.$di.'</td>
							<td>'.$df.'</td>
						';
					$t.='</tr>';
				} else {
					$n++;
				}
			}
		}
		$t.='</tbody></table>';
		// $s.=get_alert(json_encode($auid),'info');
		$s.=get_alert('<strong>Lista foto mancanti</strong><br /><strong>'.$n.'</strong> foto esistenti - <strong>'.$n1.'</strong> foto mancanti','info');
		$s.='<div class="row bg-light">';
			$s.='<div class="col-sm-2">';
				$s.='<button class="btn btn-info btn-sm btn-block" act="toggleDTX" tabledom="'.$nt.'">Toggle DataTables</button>';
			$s.='</div>';
			$s.='<div class="col-sm-10">';
			$s.='</div>';
		$s.='</div>';
		$s.='<br />'.$t;

	// } else {
		// $s.=get_alert('<strong>Ottimo!</strong> Nessuna anagrafica senza foto','success');
	// }
	return $s;	
}
function get_rfe_foto(){
//	global $conn_new;	// /var/www/html/rac/img/l.abeni.jpg
	$s='';
//	$aauid=array();
//	$dir='/var/www/html/rac/img';
//	$a = array_diff(scandir($dir), array('..', '.'));		// tolgo . e ..
//	foreach($a as $f){
//		if (!is_dir($dir.'/'.$f)){			// non è una dir
//			$ff=$dir.'/'.$f;
//			$i = pathinfo($ff);
//			if (strtolower($i['extension'])=='jpg') {	
//				$auid[] = $i['filename'];
//			}								
//		}
//	}	
//	$n=count($auid);
//	if ($n > 0){
		$nt='tb-foto_esistenti';
		$t='<table id="'.$nt.'" class="table table-striped table-sm table-responsive"><thead><tr><th></th><th></th><th>uid</th><th>mail</th><th>cognome e nome</th><th>ruolo</th><th>afferenza</th><th>inquadramento</th><th>inizio</th><th>scadenza</th></tr></thead><tbody>';
		$n=0;
		$n1=0;
		for($i=0; $i<count($_SESSION['IAM']['ab_can']['LDAP_UID']); $i++){
			if ($_SESSION['IAM']['ab_can']['LDAP_UID'][$i] != ''){

				$ff='';
				try {
					$ff = file_get_contents("https://dotnetu.local/ms/getfoto.php?toc===AW55EVNdEeBNmaF1zIxIHQsBzU&uid=".$_SESSION['IAM']['ab_can']['LDAP_UID'][$i]);
					$im='data:image/jpeg;base64,'.$ff;
				} catch(Exception $e) {
					// $errore.='<br />'.$e->getMessage();
					$im='broken.jpg';
				}

				if ($ff != ''){
					$n1++;
					$t.='<tr>';
						$t.='<td><button title="Carica una nuova foto" class="btn btn-success btn-sm btn-block" act="cn_foto" dom="gf_bottom" uid_fm="'.$_SESSION['IAM']['ab_can']['LDAP_UID'][$i].'">Carica</button></td>';
						// $pf='/rac/img/'.$_SESSION['IAM']['ab_can']['LDAP_UID'][$i].'.jpg';
						// $pf='data:image/png;base64, '.base64_encode($ff);
						$t.='<td><img style="height: 100px; background-color: rgba(0,0,255,0.1);" src="'.$im.'" /></td>';
						$d=$_SESSION['IAM']['ab_can']['DT_RAP_FIN'][$i];
						$df=substr($d,6,4).'/'.substr($d,3,2).'/'.substr($d,0,2);
						$d=$_SESSION['IAM']['ab_can']['DT_RAP_INI'][$i];
						$di=substr($d,6,4).'/'.substr($d,3,2).'/'.substr($d,0,2);
						$t.='<td>'.$_SESSION['IAM']['ab_can']['LDAP_UID'][$i].'</td>
							<td>'.$_SESSION['IAM']['ab_can']['MAIL'][$i].'</td>
							<td>'.$_SESSION['IAM']['ab_can']['COGNOME'][$i].' '.$_SESSION['IAM']['ab_can']['NOME'][$i].'</td>
							<td>'.$_SESSION['IAM']['ab_can']['KR'][$i].' '.$_SESSION['IAM']['ab_can']['DR'][$i].'</td>
							<td>'.$_SESSION['IAM']['ab_can']['KA'][$i].' '.$_SESSION['IAM']['ab_can']['DA'][$i].'</td>
							<td>'.$_SESSION['IAM']['ab_can']['KI'][$i].' '.$_SESSION['IAM']['ab_can']['DI'][$i].'</td>
							<td>'.$di.'</td>
							<td>'.$df.'</td>
						';
					$t.='</tr>';
				// }}
				}
			}
		}
		$t.='</tbody></table>';
		// $s.=get_alert(json_encode($auid),'info');
		$s.=get_alert('<strong>Lista foto mancanti</strong><br /><strong>'.$n.'</strong> foto esistenti - <strong>'.$n1.'</strong> foto mancanti','info');
		$s.='<div class="row bg-light">';
			$s.='<div class="col-sm-2">';
				$s.='<button class="btn btn-info btn-sm btn-block" act="toggleDTX" tabledom="'.$nt.'">Toggle DataTables</button>';
			$s.='</div>';
			$s.='<div class="col-sm-10">';
			$s.='</div>';
		$s.='</div>';
		$s.='<br />'.$t;
//	} else {
//		$s.=get_alert('<strong>Ottimo!</strong> Nessuna anagrafica senza foto','success');
//	}
	return $s;	
}
// --- ML ---
function get_dettaglio_ML(){
	global $conn_new;
	$s='<br />';
	$automatismo=false;
	// if (empty($_SESSION['IAM']['tbmailist'])){
		$sql="select * from c##sss_import.tbmailist";	
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$_SESSION['IAM']['tbmailist']=$a[2];
		} else {
			return get_alert('Impossibile caricare la lista delle ML automatiche','danger');
		}
	// }
	if (in_array($_REQUEST['nml'],$_SESSION['IAM']['tbmailist']['NOME_LISTA'])){
		$automatismo=true;
		$indice=array_search($_REQUEST['nml'],$_SESSION['IAM']['tbmailist']['NOME_LISTA']);
	} else {
		$s.='<div class="row">';
			$s.='<div class="col-sm-2"></div>';
			$s.='<div class="col-sm-8">';
				$s.=get_alert('<h3>ML '.$_REQUEST['nml'].' inesistente !</h3>','danger');
			$s.='</div>';
			$s.='<div class="col-sm-2"></div>';
		$s.='</div>';		
		return $s;
	}

	$s.='<div class="alert alert-dark">';
		$s.='<div class="row alert alert-light text-danger">';
			$s.='<div class="col-sm-12 text-center"><h2>'.$_REQUEST['nml'].'</h2></div>';
		$s.='</div>';

		$s.='<div class="row">';
			$s.='<div class="col-sm-4">';
				$s.='<div class="row">';
					$s.='<div class="col-sm-4">';
						// $s.='<button class="btn btn-danger btn-block" act="del_ml" dom="mm" nml="'.$_REQUEST['nml'].'">Elimina ML</button>'; 
					$s.='</div>';
					$s.='<div class="col-sm-4">';
						// if ($automatismo){
							// $s.='<button class="btn btn-warning btn-block" act="agg_ml" dom="ml_up" nml="'.$_REQUEST['nml'].'">Aggiorna ML</button>';
						// }
					$s.='</div>';
					$s.='<div class="col-sm-4">';
						if ($automatismo){
							$s.='<button class="btn btn-danger btn-block" act="dea_ml" dom="mm" nml="'.$_REQUEST['nml'].'">Cancella autom.</button>'; // cancella automatismo e rilegge la ML

						} else {
							$s.='<button class="btn btn-success btn-block" act="cra_ml" dom="mm" nml="'.$_REQUEST['nml'].'">Crea autom.</button>'; // crea automatismo e rilegge la ML
						}
					$s.='</div>';
				$s.='</div>';
				$s.='<div class="row py-2">';
					$s.='<div class="col-sm-4">';
						$s.='<button class="btn btn-primary btn-block" tipo="nn" act="listmode_ml" dom="mm">NULL no sssup</button>';
					$s.='</div>';
					$s.='<div class="col-sm-4">';
						$s.='<button class="btn btn-primary btn-block" tipo="n" act="listmode_ml" dom="mm">NULL attuali</button>';
					$s.='</div>';
					$s.='<div class="col-sm-4">';
						$s.='<button class="btn btn-primary btn-block" tipo="f" act="listmode_ml" dom="mm">FEED attuali</button>';
					$s.='</div>';
				$s.='</div>';
				// $s.='<div class="row alert alert-success py-2"><div class="col-sm-12" id="out_agg_list"></div></div>'; // output aggiornamento lista
			$s.='</div>';
			
			$s.='<div class="col-sm-8 alert alert-warning py-1">';
/*			
				$s.='<form id="f_new_fn">';
					$s.='<div class="row">';
						$s.='<div class="col-sm-5">';
							$s.='<textarea class="form-control form-control-sm" title="Null to subscribe" placeholder="Null to subscribe" id="new_null" name="new_null" rows="4"></textarea>';
						$s.='</div>';
						$s.='<div class="col-sm-5">';
							$s.='<textarea class="form-control form-control-sm" title="Feed to subscribe" placeholder="Feed to subscribe" id="new_feed" name="new_feed" rows="4"></textarea>';
						$s.='</div>';
						$s.='<div class="col-sm-2">';
							$s.='<button class="btn btn-success btn-block" act="ns_ml" dom="mm" nml="'.$_REQUEST['nml'].'" tab="new_fn">Subscribe</button>';
						$s.='</div>';
					$s.='</div>';
				$s.='</form>';

				$s.='<div class="row py-1">';
					$s.='<div class="col-sm-2">';
						$s.='<button class="btn btn-primary btn-block" act="asu_ml" dom="f_new_fn" nml="'.$_REQUEST['nml'].'" tab="new_subs" getf="new_null,new_feed" tipo="n">in NULL <i class="fas fa-arrow-up"></i></button>';
					$s.='</div>';
					$s.='<div class="col-sm-2">';
						$s.='<button class="btn btn-primary btn-block" act="asu_ml" dom="row_nf" nml="'.$_REQUEST['nml'].'" tab="new_subs" getf="LISTAFIX" tipo="nf">in NULL fissi <i class="fas fa-arrow-down"></i></button>';
					$s.='</div>';
					$s.='<div class="col-sm-4">';
						$s.='<form id="f_new_subs">';
							$ss='';
							for ($j=0; $j < count($_SESSION['IAM']['ab_can']['MAIL']); $j++) {
								if ($_SESSION['IAM']['ab_can']['MAIL'][$j]!=''){
									$ss.='<option data-tokens="'.$_SESSION['IAM']['ab_can']['MAIL'][$j].'" value="'.$_SESSION['IAM']['ab_can']['MAIL'][$j].'">'.$_SESSION['IAM']['ab_can']['COGNOME'][$j].' '.$_SESSION['IAM']['ab_can']['NOME'][$j].'</option>';
								}
							}
							$s.='<select name="nsub" id="nsub" class="form-control form-control-sm" value="" data-live-search="true" multiple data-actions-box="true" data-size="8" sep >
								<option data-tokens="0" value="0"></option>
								'.$ss.'
							</select>';
						$s.='</form>';			
					$s.='</div>';
					$s.='<div class="col-sm-2">';
						$s.='<button class="btn btn-primary btn-block" act="asu_ml" dom="f_new_fn" nml="'.$_REQUEST['nml'].'" tab="new_subs" getf="new_null,new_feed" tipo="f">in FEED <i class="fas fa-arrow-up"></i></button>';
					$s.='</div>';
				$s.='</div>';
*/
			$s.='</div>';
		$s.='</div>';

	$s.='</div>';
	$s.='<div id="ml_up"></div>';
	
	if ($automatismo){
		// automazione
		$s.='<form id="f_automatismo" class="alert alert-warning">';
			$s.='<div class="row alert alert-dark">';
				$s.='<div class="col-sm-12">';
					$s.='<h4 class="text-center">Automatismo</h4>';
				$s.='</div>';
			$s.='</div>';
			
			$s.='<div class="row">';
				$s.='<div class="col-sm-2 text-right">';
					$s.='<label class="control-label" for="NOTE">NOTE:</label>';
				$s.='</div>';
				$s.='<div class="col-sm-8">';
					$s.='<textarea class="form-control form-control-sm" title="note" placeholder="note" id="NOTE" name="NOTE" rows="3">'.implode("'",explode("''",$_SESSION['IAM']['tbmailist']['NOTE'][$indice])).'</textarea>';
				$s.='</div>';

				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-success btn-block" act="sau_ml" dom="mm" tab="automatismo" nml="'.$_REQUEST['nml'].'">Salva autom.</button>';
				$s.='</div>';

			$s.='</div>';
			
			$s.='<div class="row py-1">';
				$s.='<div class="col-sm-2 text-right">';
					$s.='<label class="control-label" for="DT_RUN">ultima esecuzione:</label>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<p id="DT_RUN">'.implode("'",explode("''",$_SESSION['IAM']['tbmailist']['DT_RUN'][$indice])).'</p>';
				$s.='</div>';
				$s.='<div class="col-sm-4 text-right">';
					$s.='<label class="control-label" for="ATTIVO">Stato:</label>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					// $css='danger'; $css='#FF0000'; $c=''; $t='NON ATTIVO';
					$c='';
					if($_SESSION['IAM']['tbmailist']['ATTIVO'][$indice]==1){
						// $css='success'; $css='#00AA00'; $t='ATTIVO'; 
						$c='checked';
					}
					// $s.='<div class="custom-control custom-switch">';
						
						$s.='<input type="checkbox" '.$c.' id="ATTIVO" name="ATTIVO" data-toggle="toggle" data-on="ATTIVO" data-off="NON ATTIVO" data-onstyle="success" data-offstyle="danger" data-height="25px" data-width="150px" ckb>';
						
						// $s.='<input type="checkbox" class="custom-control-input" id="ATTIVO" name="ATTIVO"'.$c.'>';
						// $s.='<label class="custom-control-label" for="ATTIVO">'.$t.'</label>';
					// $s.='</div>';
					// $s.='<p id="ATTIVO" class="bg-'.$css.' text-white text-center"><strong>'.$c.'</strong></p>';
				$s.='</div>';
			$s.='</div>';
			
			$s.='<div class="row">';
				$s.='<div class="col-sm-2 text-right">';
					$s.='<label class="control-label" for="QUERY">query per feed:</label>';
				$s.='</div>';
				$s.='<div class="col-sm-8">';
					$s.='<textarea class="form-control form-control-sm" title="query per feed" placeholder="query per feed" id="QUERY" name="QUERY" rows="3">'.implode("'",explode("''",$_SESSION['IAM']['tbmailist']['QUERY'][$indice])).'</textarea>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-primary btn-block" act="tq_ml" tipo="f" dom="mm" tab="automatismo">Prova</button>';
				$s.='</div>';
			$s.='</div>';
			
			$s.='<div class="row">';
				$s.='<div class="col-sm-2 text-right">';
					$s.='<label class="control-label" for="QUERY2">query per null:</label>';
				$s.='</div>';
				$s.='<div class="col-sm-8">';
					$s.='<textarea class="form-control form-control-sm" title="query per null" placeholder="query per null" id="QUERY2" name="QUERY2" rows="3">'.implode("'",explode("''",$_SESSION['IAM']['tbmailist']['QUERY2'][$indice])).'</textarea>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-primary btn-block" act="tq_ml" tipo="n" dom="mm" tab="automatismo">Prova</button>';
				$s.='</div>';
			$s.='</div>';
			
			$s.='<div id="row_nf" class="row">';
				$s.='<div class="col-sm-2 text-right">';
					$s.='<label class="control-label" for="LISTAFIX">null fissi:</label>';
				$s.='</div>';
				$s.='<div class="col-sm-8">';
					$s.='<textarea class="form-control form-control-sm" title="null fissi" placeholder="null fissi" id="LISTAFIX" name="LISTAFIX" rows="3">'.implode("'",explode("''",$_SESSION['IAM']['tbmailist']['LISTAFIX'][$indice])).'</textarea>';
				$s.='</div>';
				$s.='<div class="col-sm-2">';
					$s.='<button class="btn btn-primary btn-block" act="clearnull_ml" tipo="n" dom="mm" tab="automatismo">Clear in Null query</button>';
					$s.='<button class="btn btn-primary btn-block" act="sort_fix_ml" tipo="n" dom="mm" tab="automatismo">Sort null Fix</button>';
					
				$s.='</div>';
			$s.='</div>';

		$s.='</form>';		
	}		


	// $s.=get_subscribers_ML();
	$a=get_subscribers_ML();

	$s.=get_alert('Trovati '.count($a['UID']).' membri','info');

	if (count($a['UID']) > 0){
		// $_SESSION['IAM']['als']=array('UID'=>$alsj->UID,'MODE'=>$alsj->MODE);
		$_SESSION['IAM']['als']=$a;
		$s.='<br />'.get_table_data($_SESSION['IAM']['als'],'','','subscribers_ml');

		// $ls=shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABListSubscribers '.$_REQUEST['nml']);
		// $alsj=json_decode($ls);

		// $_SESSION['IAM']['als']=array('UID'=>$alsj->UID,'MODE'=>$alsj->MODE);
		// $s.='<br />'.get_table_data($_SESSION['IAM']['als'],'','','subscribers_ml');
		
		// if ($_SESSION['IAM']['uid_login'] == 'a.bongiorni'){$s.='<pre>'.get_alert($ls).'</pre>';}
	}
	return $s;
}
function get_query_ML(){
	// testa la query scritta nel campo
	global $conn_new;
	$s='';
	if ($_REQUEST['tipo']=='f'){$sql=$_REQUEST['QUERY'];}
	if ($_REQUEST['tipo']=='n'){$sql=$_REQUEST['QUERY2'];}
	if ($sql!=''){
		$sql=implode("'",explode("''",$sql));
		$a=load_db($conn_new,$sql,'o');
		if (is_array($a)){
			if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
				// $msg=get_table_data($a[2],'','','query_result');
				$msg=implode(', ',$a[2]['MAIL']);
			} else {
				$msg='<div class="alert alert-danger"><strong>Nessun risultato per la query</div>';
			}
		} else {
			$msg='<div class="alert alert-danger"><strong>Query errata</div>';
		}
		if (!empty($a[2]['MAIL'])){
			$r=array('tit' => 'risultato query ( '.count($a[2]['MAIL']).' record )', 'msg' => $msg);
		} else {
			$r=array('tit' => 'Query errata o senza risultato', 'msg' => $msg);
		}
		$s=json_encode($r);	
	}
	return $s;
}
function get_mode_ML(){
	// lista le sottoscrizioni corrispondenti al mode scelto
	$s=''; $msg=''; $tit='';
	if (!empty($_SESSION['IAM']['als'])){
		for ($i=0; $i < count($_SESSION['IAM']['als']['UID']); $i++) {
			$do=false;
			switch ($_REQUEST['tipo']) {
				case 'n':	// null
					$tit='null';
					if (strtolower($_SESSION['IAM']['als']['MODE'][$i])=='null'){$do=true;}
					break;
				case 'nn':	// null no sssup
					$tit='null no ssssup';
					if (strtolower($_SESSION['IAM']['als']['MODE'][$i])=='null' and !strpos($_SESSION['IAM']['als']['UID'][$i],'@sssup.it')){$do=true;}
					break;
				case 'f':	// feed
					$tit='feed';
					if (strtolower($_SESSION['IAM']['als']['MODE'][$i])=='feed'){$do=true;}
					break;
			}					
			if ($do){
				if ($msg != ''){$msg.=', ';}
				$msg.=$_SESSION['IAM']['als']['UID'][$i];
			}
		}
	}
	$r=array('tit'=>$tit,'msg'=>$msg);
	$s=json_encode($r);
	return $s;
}
/*
function get_subscribe_ML(){
	if (!empty($_REQUEST['nsub'])){
		if (isset($_REQUEST['conferma'])){
			// esegui sottoscrizione
			$tit='<h2>Sottoscrizione confermata</h2>';
			$msg="La sottoscrizione di <strong>".$_REQUEST['nsub']."</strong> come <strong>".($_REQUEST['tipo']=='n'?'null':'feed')."</strong> nella lista <strong>".$_REQUEST['nml']."</strong> &egrave; stata eseguita !";
			$stl='bg-info text-center text-white';
			$btn='';
			$domf='ml_bottom';
			$domc=get_dettaglio_ML();
		} else {
			$tit='<h2>sottoscrivi nuovo</h2>';
			$msg="Confermi la sottoscrizione di <strong>".$_REQUEST['nsub']."</strong> come <strong>".($_REQUEST['tipo']=='n'?'null':'feed')."</strong> nella lista <strong>".$_REQUEST['nml']."</strong> ?";
			$stl='bg-primary text-center text-white';
			$btn='<button id="modal-ok" class="btn btn-success btn-block" data-dismiss="modal" nsub="'.$_REQUEST['nsub'].'" tipo="'.$_REQUEST['tipo'].'" nml="'.$_REQUEST['nml'].'" act="nsu_ml" dom="mm" conferma="y">Conferma sottoscrizione</button><button id="modal-ko" class="btn btn-dark btn-block" data-dismiss="modal">Annulla</button>';
			$domf='';
			$domc='';
		}
	} else {
		$tit='<h2>Niente da sottoscrivere</h2>';
		$msg="";
		$stl='bg-warning text-center text-white';
		$btn='';
		$domf='';
		$domc='';
	}
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn, 'domf' => $domf, 'domc' => $domc);
	$s=json_encode($r);		
	return $s;
}
function get_unsubscribe_ML(){
		if (isset($_REQUEST['conferma'])){
		// esegui cancellazione
		$tit='<h2>Cancellazione confermata</h2>';
		$msg='';
		$msg=shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABList '.$_REQUEST['nml'].' unsubsribe '.$_REQUEST['uid']);
		$msg.="<br />La cancellazione di <strong>".$_REQUEST['uid']."</strong> dalla lista <strong>".$_REQUEST['nml']."</strong> &egrave; stata eseguita !";
		$stl='bg-danger text-center text-white';
		$btn='';
		$domf='ml_bottom';
		$domc=get_dettaglio_ML();
	} else {
		$tit='<h2>cancella</h2>';
		$msg="Confermi la cancellazione di <strong>".$_REQUEST['uid']."</strong> dalla lista <strong>".$_REQUEST['nml']."</strong> ?";
		$stl='bg-primary text-center text-white';
		$btn='<button id="modal-ok" class="btn btn-success btn-block" data-dismiss="modal" uid="'.$_REQUEST['uid'].'" nml="'.$_REQUEST['nml'].'" act="unsub_ml" dom="mm" conferma="y">Conferma cancellazione</button><button id="modal-ko" class="btn btn-dark btn-block" data-dismiss="modal">Annulla</button>';
		$domf='';
		$domc='';
	}
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn, 'domf' => $domf, 'domc' => $domc);
	$s=json_encode($r);		
	return $s;
}
function get_new_to_subscribe_ML(){
	$s='';	// contenuto della form f_new_fn
	if ($t=$_REQUEST['tipo']=='nf'){
		$nn=implode('',explode(' ',$_REQUEST['LISTAFIX'])); // tolgo gli spazi
		$ann=array_diff(explode(',',$nn), array(''));
		if (!empty($_REQUEST['nsub'])){
			if (is_array($_REQUEST['nsub'])){
				$anew=$_REQUEST['nsub'];
			} else {
				$anew=array(trim($_REQUEST['nsub']));
			}
			foreach($anew as $new){
				if (!in_array($new,$ann)){$ann[]=trim($new);}
			}
		}
		$nn=implode(', ',$ann);
		$s.='<div class="col-sm-2 text-right">';
			$s.='<label class="control-label" for="LISTAFIX">null fissi:</label>';
		$s.='</div>';
		$s.='<div class="col-sm-8">';
			$s.='<textarea class="form-control form-control-sm" title="null fissi" placeholder="null fissi" id="LISTAFIX" name="LISTAFIX" rows="3">'.implode("''",explode("'",$nn)).'</textarea>';
		$s.='</div>';
	} else {
		$nn=implode('',explode(' ',$_REQUEST['new_null'])); // tolgo gli spazi
		$nf=implode('',explode(' ',$_REQUEST['new_feed'])); // tolgo gli spazi
		if ($_REQUEST['nsub']!=''){
			if (is_array($_REQUEST['nsub'])){
				$anew=$_REQUEST['nsub'];
			} else {
				$anew=array(trim($_REQUEST['nsub']));
			}
			$t=$_REQUEST['tipo'];
			$ann=array_diff(explode(',',$nn), array(''));
			$anf=array_diff(explode(',',$nf), array(''));
			foreach($anew as $new){
				if($t=='n'){if (!in_array($new,$ann) and !in_array($new,$anf)){$ann[]=trim($new);}}
				if($t=='f'){if (!in_array($new,$ann) and !in_array($new,$anf)){$anf[]=trim($new);}}
			}
			$nn=implode(', ',$ann);
			$nf=implode(', ',$anf);
		}

		$s.='<div class="row">';
			$s.='<div class="col-sm-5">';
				$s.='<textarea class="form-control form-control-sm" title="Null to subscribe" placeholder="Null to subscribe" id="new_null" name="new_null" rows="4">'.$nn.'</textarea>';
			$s.='</div>';
			$s.='<div class="col-sm-5">';
				$s.='<textarea class="form-control form-control-sm" title="Feed to subscribe" placeholder="Feed to subscribe" id="new_feed" name="new_feed" rows="4">'.$nf.'</textarea>';
			$s.='</div>';
			$s.='<div class="col-sm-2">';
				$s.='<button class="btn btn-success btn-block" act="ns_ml" dom="mm" nml="'.$_REQUEST['nml'].'" tab="new_fn">Subscribe</button>';
			$s.='</div>';
		$s.='</div>';
	}
	return $s;
}
function get_ns_ML(){
	$s='';
	if (isset($_REQUEST['conferma'])){
		// esegui sottoscrizione
		$tit='<h2>Sottoscrizioni confermate nella lista <strong>'.$_REQUEST['nml'].'</strong></h2>';
		$msg='La sottoscrizione di ';

		if (!empty($_REQUEST['new_feed'])){
			$lf=implode('',explode(' ',$_REQUEST['new_feed']));
			$alf=explode(',',$lf);
			foreach($alf as $lfi){
				$msg.=shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABList '.$_REQUEST['nml'].' feed '.trim($lfi));
			}
			$msg.='<div class="alert alert-success"><strong>FEED</strong><br>'.$_REQUEST['new_feed'].'</div>';
		} 
		if (!empty($_REQUEST['new_null'])){
			$ln=implode('',explode(' ',$_REQUEST['new_null']));
			$aln=explode(',',$ln);
			foreach($aln as $lni){
				$msg.=shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABList '.$_REQUEST['nml'].' null '.trim($lni));
			}
			$msg.='<div class="alert alert-info"><strong>NULL</strong><br>'.$_REQUEST['new_null'].'</div>';
		} 
		$msg.='&egrave; stata eseguita !';
		$stl='bg-success text-center text-white';
		$btn='';
		$domf='ml_bottom';
		$domc=get_dettaglio_ML();
	} else {
		$tit='<h2>sottoscrivi nuovi nella lista <strong>'.$_REQUEST['nml'].'</strong></h2>';
		$msg='Confermi la sottoscrizione di ';
		if (!empty($_REQUEST['new_feed'])){
			$msg.='<div class="alert alert-success"><strong>FEED</strong><br>'.$_REQUEST['new_feed'].'</div>';
		} 
		if (!empty($_REQUEST['new_null'])){
			$msg.='<div class="alert alert-info"><strong>NULL</strong><br>'.$_REQUEST['new_null'].'</div>';
		} 
		$msg.='?';
		$stl='bg-primary text-center text-white';
		$btn='<button id="modal-ok" class="btn btn-success btn-block" data-dismiss="modal" new_feed="'.$_REQUEST['new_feed'].'" new_null="'.$_REQUEST['new_null'].'" nml="'.$_REQUEST['nml'].'" act="ns_ml" dom="mm" conferma="y">Conferma sottoscrizione</button><button id="modal-ko" class="btn btn-dark btn-block" data-dismiss="modal">Annulla</button>';
		$domf='';
		$domc='';
	}
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn, 'domf' => $domf, 'domc' => $domc);
	$s=json_encode($r);			
	return $s;
}
*/
function get_salva_automatismo_ML(){
	global $conn_new;
	$nml=$_REQUEST['nml'];
	$sql = "select * from c##sss_import.TBMAILIST where NOME_LISTA='".$nml."'";
	$a=load_db($conn_new,$sql,'o');
	if ($a[0] > 0 and $a[1] > 0) {
		// esiste ... lo modifico
		$tit='<h2>Modifica automatismo</h2>';
		$sql = "update c##SSS_IMPORT.TBMAILIST set ";
		// arriva da json con doppi apici raddoppiati - giusto per inserimento in db
		$sqlt=" NOTE=null"; if (!empty($_REQUEST['NOTE'])){$sqlt=" NOTE='".implode("''",explode("'",$_REQUEST['NOTE']))."'";} $sql.=$sqlt;
		$sqlt=",ATTIVO=0"; if (!empty($_REQUEST['ATTIVO'])){if($_REQUEST['ATTIVO']=='on'){$sqlt=",ATTIVO=1";}} $sql.=$sqlt;
		$sqlt=",QUERY=null"; if (!empty($_REQUEST['QUERY'])){$sqlt=",QUERY='".implode("''",explode("'",$_REQUEST['QUERY']))."'";} $sql.=$sqlt;
		$sqlt=",QUERY2=null"; if (!empty($_REQUEST['QUERY2'])){$sqlt=",QUERY2='".implode("''",explode("'",$_REQUEST['QUERY2']))."'";} $sql.=$sqlt;
		$sqlt=",LISTAFIX=null"; if (!empty($_REQUEST['LISTAFIX'])){$sqlt=",LISTAFIX='".implode("''",explode("'",$_REQUEST['LISTAFIX']))."'";} $sql.=$sqlt;
		$sql.=" where NOME_LISTA='".$nml."'";
		$a=load_db($conn_new,$sql,'o');
	} else {
		// non esiste ... lo creo
		$tit='<h2>Inserimento automatismo</h2>';
		// insert
		$sql = "insert into c##SSS_IMPORT.TBMAILIST (NOME_LISTA) values ('".$nml."')";
		$a=load_db($conn_new,$sql,'o');
		$sql="select * from c##sss_import.tbmailist";	
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$_SESSION['IAM']['tbmailist']=$a[2];
		}
	}
	$msg='Automatismo della Lista <strong>'.$nml.'</strong> salvata !';
	// $msg.='<br />'.json_encode($_REQUEST);
	// $msg.='<br />'.$sql;
	$stl='bg-success text-center text-white';
	$btn='';
	$domf='ml_bottom';
	$domc=get_dettaglio_ML();
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn, 'domf' => $domf, 'domc' => $domc);
	return json_encode($r);	
}
/*
function get_vl_ML() {					// @@@@ OBSOLETO verifica di una singola lista	
		global $conn_new;
		if (empty($_REQUEST['nml'])){
			return get_alert('Impossibile recuperare il nome della lista','danger');
		}
		$nml=$_REQUEST['nml'];
		$sql = "select * from c##sss_import.TBMAILIST where attivo=1 and NOME_LISTA='".$nml."'";
		$y=load_db($conn_new,$sql,'o');
		if ($y[0] == 0 or $y[1] == 0){
			return get_alert('Lista <strong>'.$nml.'</strong> non trovata','danger');
		}
		$x=$y[2];
		$lista=$x['NOME_LISTA'][0];
		$sql_feed=trim($x['QUERY'][0]);
		$sql_null=trim($x['QUERY2'][0]);
		$s_fix=str_replace(' ','',trim($x['LISTAFIX'][0]));
		$s='';

		$oo='Aggiornamento lista: <strong>'.$lista.'</strong><br>'; 
		// $oo.=$sql_feed.'<br>'.$sql_null.'<br>'.$s_fix.'<br>';
		
		// feed
		$sf='';
		if ($sql_feed!=''){
			$r=load_dbx($conn_new,$sql_feed,'o',true,'o');
			$ro=$r['data']; $rj=$r['json']; $nc=$r['colonne']; $nr=$r['righe'];
			$sf=implode(',',$r['data']['MAIL']);
		}

		// null
		$sn=$s_fix;
		if ($sql_null!='' and substr($sql_null,0,2)!='--'){
			$r=load_dbx($conn_new,$sql_null,'o',true,'o');
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
					if (trim($sn,' ') == '' and $asubs['MODE'][$j] == 'null') {
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
		$sql="update C##SSS_IMPORT.TBMAILIST set DT_RUN=sysdate where NOME_LISTA='".$nml."'";
		$a=load_db($conn_new,$sql,'o');
		return $oo;
	}
function get_vl_all_ML(){				// @@@@ OBSOLETO verifica tutte le liste
	global $conn_new;
	$s='<hr>';
	$sql = "select * from c##sss_import.TBMAILIST where attivo=1 order by NOME_LISTA";
	$y=load_db($conn_new,$sql,'o');
	if ($y[0] == 0 or $y[1] == 0){
		return get_alert('Lista <strong>Nessuna lista automatica trovata</strong>','danger');
	}
	for($i=0; $i<count($y[2]['NOME_LISTA']); $i++){
		$_REQUEST['nml']=$y[2]['NOME_LISTA'][$i];
		$s.=get_vl_ML();
		$s.='<hr>';
	}		
	return $s;
}
*/
function get_lista_ML(){
	global $conn_new;
	$s='';
	// --- lettura lista ML

//	$ml=shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABListLists ');
//	$aml=json_decode($ml,true);
//	if (!is_array($aml)){
//		$s=get_alert('Impossibile recuperare la lista delle ML','danger');
//		return $s;
//	}
//	$a=$aml['ML'];
//	sort($a);
//	// $a=array('ML'=>$aml['ML']);
	// $sql="select * from c##sss_import.tbmailist where attivo=1";	
	$sql="select * from c##sss_import.tbmailist order by NOME_LISTA";	
	$x=load_db($conn_new,$sql,'o');
	if ($x[0]>0 and $x[1]>0) {	// almeno una riga e almeno una colonna
		$tbmailist=$x[2];
	}
	
	$s.='<table class="table table-striped table-sm table-responsive" id="tb-lista_ML">
		<thead>
			<tr>
				<th></th>
				<th>ML</th>
				<th>query feed</th>
				<th>query null</th>
				<th>null fissi</th>
				<th>Note</th>
				<th>ultimo agg.</th>
				<th>conteggi</th>
			</tr>
		</thead>
		<tbody>';
	// for($i=0; $i<count($a); $i++){
	for($i=0; $i<count($tbmailist['NOME_LISTA']); $i++){
//		$css='primary';
//		if(!empty($tbmailist)){if (in_array($a[$i],$tbmailist['NOME_LISTA'])){
			$css='success';	// è nelle liste automatiche
//			$n=array_search($a[$i], $tbmailist['NOME_LISTA']);
//		}}
//		// conteggio dei feed, null e other
//		$jmld=shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABListSubscribers '.$a[$i]);
//		$mld=json_decode($jmld);
//		$amld=array('UID'=>$mld->UID,'MODE'=>$mld->MODE);
//		if (empty($amld)){
//			$nnn='vuoto';
//		} else {
//			// UID, MODE
//			$nf=0; $nn=0; $nx=0;
//			for($j=0; $j<count($amld['UID']); $j++){
//				if ($amld['MODE'][$j]=='feed'){$nf++;}
//				if ($amld['MODE'][$j]=='null'){$nn++;}
//				if ($amld['MODE'][$j]!='null' and $amld['MODE'][$j]!='feed'){$nx++;}
//			}
//			$nnn='<strong>Tot: '.count($amld['UID']).'</strong> - Feed: '.$nf.' - Null: '.$nn.' - Other: '.$nx;
//		}
		
		$_REQUEST['nml']=$tbmailist['NOME_LISTA'][$i];
		$a=get_subscribers_ML();
		$nf=0; $nn=0;
		foreach ($a['MODE'] as $ai){if ($ai=='feed'){$nf++;} if ($ai=='null'){$nn++;}}
		$nnn='<strong>Tot: '.count($a['UID']).'</strong> - Feed: '.$nf.' - Null: '.$nn;
		if ($tbmailist['ATTIVO'][$i]==0){$css='danger';}
		$s.='<tr class="alert-'.$css.'">';
			$s.='<td><button class="btn btn-primary btn-sm btn-block" act="dett_ml" nml="'.$tbmailist['NOME_LISTA'][$i].'" dom="ml_bottom">Dettaglio</button></td>';
			if (strpos($css,'success') !== false){
				$s.='<td><h4>'.$tbmailist['NOME_LISTA'][$i].'</h4></td>';
				$s.='<td>'.(!empty($tbmailist['QUERY'][$i])?'feed gestito':'').'</td>';
				$s.='<td>'.(!empty($tbmailist['QUERY2'][$i])?'null gestito':'').'</td>';
				$s.='<td>'.(!empty($tbmailist['LISTAFIX'][$i])?'null gestito':'').'</td>';
				$s.='<td>'.$tbmailist['NOTE'][$i].'</td>';
				$s.='<td>'.substr($tbmailist['DT_RUN'][$i],0,10).'</td>';
			} else {
				$s.='<td>'.$tbmailist['NOME_LISTA'][$i].'</td>';
				$s.='<td></td><td></td><td></td><td></td><td></td>';
			}
			$s.='<td>'.$nnn.'</td>';
		$s.='</tr>';
	}
	$s.='</tbody></table>';

	return $s;
}
function get_sort_fix_ML(){
	if (!empty($_REQUEST['LISTAFIX'])){
		$a=explode(',',$_REQUEST['LISTAFIX']);
		$as=array();
		foreach ($a as $ai){
			$as[]=trim($ai);
		}
		sort($as);
		$tit='<h2>Sort NULL Fix</h2>';
		$stl='bg-success text-center text-white';
		$msg="Null fissi ordinati";
		$btn='';
		$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn);		
		$r['domi'][0]['domf']='LISTAFIX';
		$r['domi'][0]['domc']=implode(', ',$as);
		return json_encode($r);
	}
}
function get_clearnull_ML(){
	global $conn_new;
	
	if (isset($_REQUEST['conferma'])){
		$tit='<h2>Clear NULL in ML</h2>';
		$stl='bg-success text-center text-white';
		$msg="Null fissi sistemati, salva l'automatismo";
		$btn='';
		$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn);		$r['domi'][0]['domf']='LISTAFIX';
		$r['domi'][0]['domc']=$_REQUEST['nfdm'];
		return json_encode($r);
	} else {
		$tit='<h2>Clear NULL in ML</h2>';
		$do=true; 
		$msg='';
		$stl='bg-warning text-center text-white';
		$btn='';
		if (empty($_REQUEST['QUERY2'])){
			$msg.=get_alert('nessuna query definita per i null','danger'); 
			$do=false;
		}
		if (empty($_REQUEST['LISTAFIX'])){
			$msg.=get_alert('nessuna mail definita per i null fissi','danger'); 
			$do=false;
		}
		if ($do) {
			$adc=array();
			$amt=array();
			$asssup=array();
			$sql=$_REQUEST['QUERY2'];	
			$a=load_db($conn_new,$sql,'o');
			if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
				// $a[2]['MAIL'] = lista delle mail definite nella query
				$anf=explode(',',str_replace(' ','',$_REQUEST['LISTAFIX']));
				// $msg.=get_alert('<strong>Mail della query ('.count($a[2]['MAIL']).')</strong><br />'.implode(', ',$a[2]['MAIL']),'primary');
				$adm=$anf;
				
				$msg.=get_alert('<strong>Null fissi ('.count($anf).')</strong>','secondary');
				$adc=array_intersect($a[2]['MAIL'], $anf);
				if (count($adc) > 0){
					$msg.=get_alert('<strong>Mail presenti in entrambi ('.count($adc).')</strong><br />'.implode(', ',$adc),'warning');
					$adm=array_merge(array_diff($anf, $adc), array_diff($adc, $anf));
				}
				
				// controllo anche se i null rimanenti contengono indirizzi di persone non più attive
				// $_SESSION['IAM']['ab_can']
				$amt0 = array_diff($adm, $_SESSION['IAM']['ab_can']['MAIL']);
				// ma devono essere stati attivi precedentemente
				// $_SESSION['IAM']['ab_csn']
				$amt=array_unique(array_intersect($_SESSION['IAM']['ab_csn']['MAIL'], $amt0));
				if (count($amt) > 0){
					$msg.=get_alert('<strong>Mail non pi&ugrave; attive ('.count($amt).')</strong><br />'.implode(', ',$amt),'danger');
					$adm=array_merge(array_diff($adm, $amt), array_diff($adc, $amt));
				}

				// controllo anche se i null contengono mail con sssup
				foreach ($adm as $admi){
					if (strpos($admi, "sssup.it") !== false){
						$asssup[]=$admi;
					}
				}
				if (count($asssup) > 0){
					$msg.=get_alert('<strong>Mail sssup ('.count($asssup).')</strong><br />'.implode(', ',$asssup),'danger');
					$adm=array_merge(array_diff($adm, $asssup), array_diff($adc, $asssup));
				}

				$msg.=get_alert('<strong>Mail da mantenere ('.count($adm).')</strong><br />'.implode(', ',$adm),'success');
				if (count($adc) > 0 or count($amt) > 0 or count($asssup) > 0) {
					$msg.='<strong>Vuoi procedere alla sistemazione dei Null fissi ?</strong>';
				}
				// $msg.=json_encode($_REQUEST);
			} else {
				$msg.=get_alert('la query non riileva nessuna mail','danger');
			}		
			if (count($adc) > 0 or count($amt) > 0 or count($asssup) > 0){
				$btn='<button id="modal-ok" class="btn btn-success btn-block" data-dismiss="modal" nfdm="'.implode(', ',$adm).'" act="clearnull_ml" dom="mm" conferma="y">Conferma sistemazione</button><button id="modal-ko" class="btn btn-dark btn-block" data-dismiss="modal">Annulla</button>';
			}
		}
		$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn);
		return json_encode($r);		
	}
}
function get_subscribers_ML(){
		global $conn_new;
		$s='';
		
		$a=array();
		$a['UID']=array();
		$a['MODE']=array();			

		if (empty($_REQUEST['nml'])){
			// return get_alert('Impossibile recuperare il nome della lista','danger');
			return $a;	// array vuoti
		}
		$nml=$_REQUEST['nml'];
		// $sql = "select * from c##sss_import.TBMAILIST where attivo=1 and NOME_LISTA='".$nml."'";
		$sql = "select * from c##sss_import.TBMAILIST where NOME_LISTA='".$nml."'";
		$y=load_db($conn_new,$sql,'o');
		if ($y[0] == 0 or $y[1] == 0){
			// return get_alert('Lista <strong>'.$nml.'</strong> non trovata','danger');
			return $a;	// array vuoti
		}
		$x=$y[2];
		$lista=$x['NOME_LISTA'][0];

		$sql_feed=''; if (!empty($x['QUERY'][0])){$sql_feed=trim($x['QUERY'][0]);}
		$sql_null=''; if (!empty($x['QUERY2'][0])){$sql_null=trim($x['QUERY2'][0]);}
		$s_fix=''; if (!empty($x['LISTAFIX'][0])){$s_fix=str_replace(' ','',trim($x['LISTAFIX'][0]));}
		$s='';

		// feed
		$sf='';
		if ($sql_feed!=''){
			$r=load_dbx($conn_new,$sql_feed,'o',true,'o');
			$ro=$r['data']; $rj=$r['json']; $nc=$r['colonne']; $nr=$r['righe'];
			if ($nr < 1){return $a;}	// la query feed deve restituire almeno 1 indirizzo
			$sf=implode(',',$r['data']['MAIL']);
		} else {
			// in feed deve esserci una definizione altrimenti non invierebbe a nessuno
			return $a;	// array vuoti
		}

		// null
		$sn=$s_fix;
		if ($sql_null!='' and substr($sql_null,0,2)!='--'){
			$r=load_dbx($conn_new,$sql_null,'o',true,'o');
			$ro=$r['data']; $rj=$r['json']; $nc=$r['colonne']; $nr=$r['righe'];
			if ($nr > 0){	// se la query ha restituito almeno 1 indirizzo
				$s=implode(',',$r['data']['MAIL']);
				if ($s_fix!=''){$sn.=',';}
				$sn.=$s;
			}
		}

		$af=explode(",", str_replace(' ','',$sf));	// lista dei feed
		$an=explode(",", str_replace(' ','',$sn));	// lista dei null
		foreach ($af as $afi){
			if (!empty($afi)){
				$a['UID'][]=$afi;
				$a['MODE'][]='feed';
			}
		}
		foreach ($an as $ani){
			if (!empty($ani)){
				$a['UID'][]=$ani;
				$a['MODE'][]='null';
			}
		}
		return $a;
}
function get_nuova_lista_ML(){
	global $conn_new;
	$s='';
	$domf='';
	$domc='';
	$tit='<h2>Nuova ML</h2>';
	$btn='';
	if (isset($_REQUEST['conferma'])){
		// controlla esistenza
		$sql="select * from c##sss_import.tbmailist where trim(lower(NOME_LISTA))='".trim(strtolower($_REQUEST['nome_nuova_lista']))."'";	
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$_REQUEST['nome_nuova_lista']=$a[2]['NOME_LISTA'][0];
			$msg=get_alert('La ML <strong>'.$_REQUEST['nome_nuova_lista'].'</strong> esiste gi&agrave; !','danger');
			$stl='bg-danger text-center text-white';
			$domf='ml_bottom';
			$_REQUEST['nml']=$_REQUEST['nome_nuova_lista'];
			$domc=get_dettaglio_ML();
		} else {
			// inserisco la nuova ML
			$sql="insert into c##sss_import.tbmailist (ID, NOME_LISTA, ATTIVO) values ((SELECT max(id)+1 AS new_id FROM c##sss_import.tbmailist), '".$_REQUEST['nome_nuova_lista']."',0)";
			$a=load_db($conn_new,$sql,'o');
			// rileggo la lista delle ML automatiche
			$sql="select * from c##sss_import.tbmailist";	
			$a=load_db($conn_new,$sql,'o');
			if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
				$_SESSION['IAM']['tbmailist']=$a[2];
			}
			$msg=get_alert('La ML '.$_REQUEST['nome_nuova_lista'].' &egrave; stata creata !','success');
			$stl='bg-success text-center text-white';
			$domf='ml_bottom';
			$_REQUEST['nml']=$_REQUEST['nome_nuova_lista'];
			$domc=get_dettaglio_ML();
		}

	} else {
		$msg=get_alert('<input type="text" name="nome_nuova_lista" id="nome_nuova_lista" class="form-control form-control-sm" placeholder="inserisci il nome della nuova lista" />','primary');
		$stl='bg-primary text-center text-white';
		$btn='<button id="modal-ok" class="btn btn-success btn-block" data-dismiss="modal" act="nuova_lista_ml" dom="mm" conferma="y" getf="nome_nuova_lista">Conferma</button><button id="modal-ko" class="btn btn-dark btn-block" data-dismiss="modal">Annulla</button>';
	}
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn, 'domf' => $domf, 'domc' => $domc);
	$s=json_encode($r);			
	return $s;	
}
function get_del_automatismo_ML(){
	global $conn_new;
	$s='';
	$domf='';
	$domc='';
	$tit='<h2>Cancella ML</h2>';
	$btn='';
	if (isset($_REQUEST['conferma'])){
		// controlla esistenza
		$sql="select * from c##sss_import.tbmailist where trim(lower(NOME_LISTA))='".trim(strtolower($_REQUEST['nml']))."'";	
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			// cancello la ML
			$sql="delete from c##sss_import.tbmailist where trim(lower(NOME_LISTA))='".trim(strtolower($_REQUEST['nml']))."'";
			$a=load_db($conn_new,$sql,'o');
			// rileggo la lista delle ML automatiche
			$sql="select * from c##sss_import.tbmailist";	
			$a=load_db($conn_new,$sql,'o');
			if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
				$_SESSION['IAM']['tbmailist']=$a[2];
			}
			$msg=get_alert('La ML '.$_REQUEST['nml'].' &egrave; stata cancellata !','success');
			$stl='bg-success text-center text-white';
			
			$domf='mail_list';
			$domc=get_tab('mail_list');
			
			// $domf='ml_bottom';
			// $domc='<br /><div class="row">';
				// $domc.='<div class="col-sm-2"></div>';
				// $domc.='<div class="col-sm-8">';
					// $domc.=get_alert('<h3>La ML '.$_REQUEST['nml'].' &egrave; stata cancellata !</h3>','success');
				// $domc.='</div>';
				// $domc.='<div class="col-sm-2"></div>';
			// $domc.='</div>';
		} else {
			$msg=get_alert('La ML <strong>'.$_REQUEST['nml'].'</strong> non esiste !','danger');
			$stl='bg-danger text-center text-white';
		}

	} else {
		$msg=get_alert('<h3>Confermi la cancellazione della ML <strong>'.$_REQUEST['nml'].'</strong> ?</h3>','danger');
		$stl='bg-danger text-center text-white';
		$btn='<button id="modal-ok" class="btn btn-danger btn-block" data-dismiss="modal" act="dea_ml" dom="mm" conferma="y" nml="'.$_REQUEST['nml'].'">Conferma</button><button id="modal-ko" class="btn btn-dark btn-block" data-dismiss="modal">Annulla</button>';
	}
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn, 'domf' => $domf, 'domc' => $domc);
	$s=json_encode($r);			
	return $s;}
// --- permessi utenti UGOV ---
function get_find_pu(){					// trova o compara
	global $conn_new;

	$s='';
	$apu=array();
	$apu['gruppi']		=	['ID_GRP','DS_GRUPPO'];
	$apu['profili']		=	['CD_PROFILO','DS_PROFILO'];
	$apu['contesti']	=	['CD_NODO','NOME_BREVE'];
	$apu['utenti']		=	['USER_NAME','DS_USER'];
	$apu['aree']			=	['CD_AREA','DS_AREA'];
	$apu['moduli']		=	['CD_MODULO','DS_MODULO'];
	$apu['funzioni']	=	['CD_FUNZ','DS_FUNZ'];
	$apu['ruoli']			=	['ID_RUOLO','DS_RUOLO'];
	$asql=rsql_pu();
	foreach($asql as $k => $sql){
		$rk=explode('_',$k);
		$do=false;
		switch ($rk[0]) {
			case 'ce':	
				if ($_REQUEST['tipo']=='t'){$do=true;}	// cerca
				if ($_REQUEST['tipo']=='g' and $rk[1]=='gruppi'){$do=true;}	
				if ($_REQUEST['tipo']=='p' and $rk[1]=='profili'){$do=true;}	
				if ($_REQUEST['tipo']=='c' and $rk[1]=='contesti'){$do=true;}	
				if ($_REQUEST['tipo']=='u' and $rk[1]=='utenti'){$do=true;}	
				if ($_REQUEST['tipo']=='a' and $rk[1]=='aree'){$do=true;}	
				if ($_REQUEST['tipo']=='m' and $rk[1]=='moduli'){$do=true;}	
				if ($_REQUEST['tipo']=='f' and $rk[1]=='funzioni'){$do=true;}	
				if ($_REQUEST['tipo']=='r' and $rk[1]=='ruoli'){$do=true;}	
				break;
			case 'vi':	if ($_REQUEST['tipo']=='v'){$do=true;}	break; // visualizza
			case 'co':	if ($_REQUEST['tipo']=='2'){$do=true;}	break; // compara
			case 'or':	if ($_REQUEST['tipo']=='o'){$do=true;}	break; // organigramma
			default:		break;
		}

//	ce_utenti, ce_aree, ce_moduli, ce_funzioni, ce_gruppi, ce_profili, ce_contesti, ce_ruoli
//	vi_gruppi, vi_profili, vi_contesti, vi_ruoli
//	co_gruppi, co_profili, co_contesti, co_ruoli
//	or_organigramma

		$ta='primary';
		switch ($rk[1]) {
			case 'utenti':				$ta='info';	break;
			case 'aree':					$ta='info';	break;
			case 'moduli':				$ta='info';	break;
			case 'funzioni':			$ta='info';	break;
			case 'gruppi':				$ta='info';	break;
			case 'profili':				$ta='info';	break;
			case 'contesti':			$ta='info';	break;
			case 'ruoli':					$ta='info';	break;
			case 'organigramma':	$ta='info';	break;
			default:													break;
		}

		if ($do){
			if (!empty($_REQUEST['od'])){ // prepara per il server CINECA
				$uu='AU_SSSUP_PROD_001'; $pp='Alb0|B0ng1'; $cc='(DESCRIPTION=(ADDRESS_LIST=(FAILOVER=on)(LOAD_BALANCE=on)(ADDRESS=(PROTOCOL=TCP)(HOST=cman01-ext.dbc.cineca.it)(PORT=5555))(ADDRESS=(PROTOCOL=TCP)(HOST=cman02-ext.dbc.cineca.it)(PORT=5555)))(CONNECT_DATA=(SERVICE_NAME=ugov_sssup_prod_ext.cineca.it)))'; $conn_cineca=getconn($uu,$pp,$cc,'o');
				$a=load_db($conn_cineca,$sql,'o');
			} else {
				$a=load_db($conn_new,$sql,'o',9);
			}
			// $s.=get_alert($sql);	// @@@@@@@@@
			if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
				$s.='<div class="row alert alert-'.$ta.' colnext"><h3>'.$rk[1].' - <span class="text-danger">'.$a[1].'</span> righe</h3></div>';
				$s.='<div>'.get_table_data($a[2],'','',$k).'</div>';
//				$s.='<br /><strong>'.$k.'</strong> '.$sql.' <strong>('.$a[0].')</strong>';
			}
		}
	}
	return $s;
}
function rsql_pu() { 					// ricalcola sql per la ricerca
	if (empty($_REQUEST['ugov1'])){$u1='';} else {$u1=$_REQUEST['ugov1'];}
	if (is_array($u1)){$u1=$u1[0];}
	if (empty($_REQUEST['ugov2'])){$u2='';} else {$u2=$_REQUEST['ugov2'];}
	if (is_array($u2)){$u2=$u2[0];}
	if (empty($_REQUEST['find_pu'])){$cerca='';} else {$cerca=$_REQUEST['find_pu'];}
	
	$asql=array();

	// ---------- template 
	// profili 
	$tp = "select distinct * from (select u.USER_NAME, u.DS_USER, pu.CD_PROFILO, p.NOME_PROFILO, p.DS_PROFILO, pu.CD_PROFILO KP from C##SSS_TABLES.FW01_USR_GRP_PROFILI pu, C##SSS_TABLES.FW01_PROFILI p, (select * from C##SSS_TABLES.FW01_USER where USER_NAME = '0000') u where pu.CD_PROFILO = P.CD_PROFILO and pu.ID_GRP IN (select ID_GRP from C##SSS_IMPORT.AB_UTENTI_GRUPPI_EX where USER_NAME = '0000') union select u.USER_NAME, u.DS_USER, pu.CD_PROFILO,  p.NOME_PROFILO, p.DS_PROFILO, p.CD_PROFILO KP from C##SSS_TABLES.FW01_USR_GRP_PROFILI pu, C##SSS_TABLES.FW01_USER u, C##SSS_TABLES.FW01_PROFILI p where pu.ID_USER=u.ID_USER and pu.CD_PROFILO=p.CD_PROFILO and u.USER_NAME='0000')";
	// contesti
	// $tc = "select distinct USER_NAME,NOME_ESTESO,NOME_PROPERTY,NOME_BREVE,DS_CAUSA_FINE,CD_NODO,CD_TIPO_NODO, CD_NODO KC from C##SSS_TABLES.V_IE_PJ_CONTESTI_UTENTE ";
	$tc = "select distinct USER_NAME,NOME_ESTESO,NOME_PROPERTY,NOME_BREVE,CD_NODO,CD_TIPO_NODO, CD_NODO KC from C##SSS_TABLES.V_IE_PJ_CONTESTI_UTENTE ";
	// gruppi
	$tg = "select distinct USER_NAME, DS_USER, ID_GRP, NOME_GRUPPO, DS_GRUPPO, ID_GRP KG from C##SSS_IMPORT.AB_UTENTI_GRUPPI_EX ";


	// ---------- query
	// --- cerca
	
	// utenti
	$c = "USER_NAME, DS_USER, EMAIL, CODFIS, case when cod_fisc is null then 0 else 1 end attivo";
	$f = "C##SSS_TABLES.FW01_USER, C##SSS_IMPORT.AB_CAN";
	$w = "(upper(USER_NAME) like '%" . strtoupper($cerca) . "%' or upper(DS_USER) like '%" . strtoupper($cerca) . "%')";
	$w .= " and trunc(dt_inizio_val) <= trunc(sysdate) and trunc(dt_fine_val) >= trunc(sysdate)";
	$w .= " and CODFIS = COD_FISC(+)";
	$o = "cognome, nome, USER_NAME";
	$s = 'select ' . $c . ' from  ' . $f . ' where ' . $w . ' order by ' . $o;
	$asql['ce_utenti']=$s;
	
	// aree
	$c = "CD_AREA, DS_AREA, FL_INSTALLATA";
	$f = "C##SSS_TABLES.FW00_AREE";
	$w = "upper(CD_AREA) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(DS_AREA) like '%" . strtoupper($cerca) . "%'";
	$o = "DS_AREA";
	$s = 'select ' . $c . ' from  ' . $f . ' where ' . $w . ' order by ' . $o;
	$asql['ce_aree']=$s;

	// moduli
	$c = "m.ID_MODULO, a.CD_AREA, a.DS_AREA, m.CD_MODULO, m.DS_MODULO";
	$f = "C##SSS_TABLES.FW00_MODULI m, C##SSS_TABLES.FW00_AREE a";
	$w = "m.CD_AREA = a.CD_AREA";
		$w .= " and (";
		$w .= " upper(a.CD_AREA) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(a.DS_AREA) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(m.CD_MODULO) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(m.DS_MODULO) like '%" . strtoupper($cerca) . "%'";
		$w .= " )";
	$o = "DS_AREA, DS_MODULO";
	$s = 'select ' . $c . ' from  ' . $f . ' where ' . $w . ' order by ' . $o;
	$asql['ce_moduli']=$s;

	// funzioni
	$c = "f.ID_FUNZ, a.CD_AREA, a.DS_AREA, m.CD_MODULO, m.DS_MODULO, f.CD_FUNZ, f.DS_FUNZ";
	$f = "C##SSS_TABLES.FW01_FUNZ f, C##SSS_TABLES.FW00_MODULI m, C##SSS_TABLES.FW00_AREE a";
	$w = "a.CD_AREA = f.CD_AREA and f.ID_MODULO = m.ID_MODULO";
		$w .= " and (";
		$w .= " upper(a.CD_AREA) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(a.DS_AREA) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(m.CD_MODULO) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(m.DS_MODULO) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(f.CD_FUNZ) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(f.DS_FUNZ) like '%" . strtoupper($cerca) . "%'";
		$w .= " )";
	$o = "a.DS_AREA,m.DS_MODULO,f.DS_FUNZ";
	$s = "select distinct " . $c . " from " . $f . " where " . $w . " order by " . $o;
	$asql['ce_funzioni']=$s;

	// gruppi
	$c = "ID_GRP, NOME_GRUPPO, DS_GRUPPO, ID_GRP KG";
	$f = "C##SSS_IMPORT.AB_UTENTI_GRUPPI_EX";
	$w = "upper(NOME_GRUPPO) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(DS_GRUPPO) like '%" . strtoupper($cerca) . "%'";
	$o = "NOME_GRUPPO";
	$s = "select distinct " . $c . " from " . $f . " where " . $w . " order by " . $o;
	$asql['ce_gruppi']=$s;

	// profili
	$c = "CD_PROFILO, NOME_PROFILO, DS_PROFILO, CD_PROFILO KP";
	$f = "C##SSS_TABLES.FW01_PROFILI";
	$w = "upper(CD_PROFILO) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(NOME_PROFILO) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(DS_PROFILO) like '%" . strtoupper($cerca) . "%'";
	$o = "NOME_PROFILO";
	$s = "select distinct " . $c . " from " . $f . " where " . $w . " order by " . $o;
	$asql['ce_profili']=$s;

	// contesti
	// $c = "NOME_PROPERTY,NOME_BREVE,DS_CAUSA_FINE,CD_NODO,CD_TIPO_NODO, CD_NODO KC";
	$c = "NOME_PROPERTY,NOME_BREVE,CD_NODO,CD_TIPO_NODO, CD_NODO KC";
	$f = "C##SSS_TABLES.V_IE_PJ_CONTESTI_UTENTE";
	$w = "upper(NOME_BREVE) like '%" . strtoupper($cerca) . "%'";
	$o = "NOME_PROPERTY,NOME_BREVE";
	$s = "select distinct " . $c . " from " . $f . " where " . $w . " order by " . $o;
	$asql['ce_contesti']=$s;

	// ruoli
	$c = "CD_AREA,DS_AREA,CD_MODULO,DS_MODULO,CD_FUNZ,DS_FUNZ,ID_RUOLO,DN_RUOLO,DS_RUOLO, ID_RUOLO KR";
	$f = "C##SSS_IMPORT.AB_UTENZE_UGOV_RUOLI_EX";
	$w = "upper(CD_AREA) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(DS_AREA) like '%" . strtoupper($cerca) . "%'";
	$w .= "or upper(CD_MODULO) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(DS_MODULO) like '%" . strtoupper($cerca) . "%'";
	$w .= "or upper(CD_FUNZ) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(DS_FUNZ) like '%" . strtoupper($cerca) . "%'";
	$w .= "or upper(DN_RUOLO) like '%" . strtoupper($cerca) . "%'";
		$w .= " or upper(DS_RUOLO) like '%" . strtoupper($cerca) . "%'";
	$o = "DS_AREA,DS_MODULO,DS_FUNZ,DS_MODULO";
	$s = "select distinct " . $c . " from " . $f . " where " . $w . " order by " . $o;
	$asql['ce_ruoli']=$s;

	// --- visualizza
	// gruppi
	$s = $tg . " where USER_NAME='" . $u1 . "'";
	$asql['vi_gruppi']=$s;

	// profili
	$s = implode($u1,explode('0000',$tp));	// tp.replace(/0000/g,u1);
	$asql['vi_profili']=$s;

	// contesti
	$s = $tc . " where USER_NAME='" . $u1 . "'";
	$asql['vi_contesti']=$s;

	// ruoli
	$c = "ID_USER, CASE WHEN COGNOME IS NULL OR NOME IS NULL THEN upper(ds_user) ELSE upper(COGNOME) || ' ' || upper(NOME) END D, ID_RUOLO, DS_AREA, DS_MODULO, DS_FUNZ, DS_RUOLO, ID_RUOLO KR"; //, ID_GRP KG, CD_PROFILO KP";
	$s = "select distinct " . $c . " from C##SSS_IMPORT.AB_UTENZE_UGOV_DIRITTI_EX where USER_NAME='" . $u1 . "'";
	$asql['vi_ruoli']=$s;

	// --- confronta
	// gruppi
	$s1 = $tg . " where user_name='" . $u1 . "'";
	$s2 = $tg . " where user_name='" . $u2 . "'";
	$w0 = "q1.ID_GRP=q2.ID_GRP(+) and q2.ID_GRP is null";
	$s01 = "select q1.* from (" . $s1 . ") q1, (" . $s2 . ") q2 where " . $w0;
	$s02 = "select q1.* from (" . $s2 . ") q1, (" . $s1 . ") q2 where " . $w0;
	$s = $s01 . " union " . $s02;
	$asql['co_gruppi']=$s;

	// profili
	$s1 = implode($u1,explode('0000',$tp));	// tp.replace(/0000/g,u1);
	$s2 = implode($u2,explode('0000',$tp));	// tp.replace(/0000/g,u2);
	$w0 = "q1.cd_profilo=q2.cd_profilo(+) and q2.cd_profilo is null";
	$s01 = "select q1.* from (" . $s1 . ") q1, (" . $s2 . ") q2 where " . $w0;
	$s02 = "select q1.* from (" . $s2 . ") q1, (" . $s1 . ") q2 where " . $w0;
	$s = $s01 . " union " . $s02;
	$asql['co_profili']=$s;

	// contesti
	$s1 = $tc . " where user_name='" . $u1 . "'";
	$s2 = $tc . " where user_name='" . $u2 . "'";
	$w0 = "q1.CD_NODO=q2.CD_NODO(+) and q2.CD_NODO is null";
	$s01 = "select q1.* from (" . $s1 . ") q1, (" . $s2 . ") q2 where " . $w0;
	$s02 = "select q1.* from (" . $s2 . ") q1, (" . $s1 . ") q2 where " . $w0;
	$s = $s01 . " union " . $s02;
	$asql['co_contesti']=$s;

	// ruoli
	$c = "ID_USER, USER_NAME, CASE WHEN COGNOME IS NULL OR NOME IS NULL THEN upper(ds_user) ELSE upper(COGNOME) || ' ' || upper(NOME) END D, ID_RUOLO, DS_AREA, DS_MODULO, DS_FUNZ, DS_RUOLO, ID_RUOLO KR";
	$o = "D, DS_AREA, DS_MODULO, DS_FUNZ, DS_RUOLO";
	$s1 = "select " . $c . " from AB_UTENZE_UGOV_DIRITTI_EX where USER_NAME='" . $u1 . "'";
	$s2 = "select " . $c . " from AB_UTENZE_UGOV_DIRITTI_EX where USER_NAME='" . $u2 . "'";
	$w0 = "q1.id_ruolo=q2.id_ruolo(+) and q2.id_ruolo is null";
	$s01 = "select q1.* from (" . $s1 . ") q1, (" . $s2 . ") q2 where " . $w0;
	$s02 = "select q1.* from (" . $s2 . ") q1, (" . $s1 . ") q2 where " . $w0;
	$s = "select * from (" . $s01 . " union " . $s02 . ") order by " . $o;
	$asql['co_ruoli']=$s;

	// --- organigramma
//	$c = 'ID_USER, COGNOME, NOME, CD_RUOLO, DS_RUOLO, DS_AFF_ORG, DS_SEDE, LDAP_UID, MAIL, TELEPHONENUMBER, RPATH, LIVELLO';
//	$f = 'AB_PERSONALE_LDAP pa,AB_FW01_USER u';
//	$w = 'pa.LDAP_UID = u.USER_NAME';
//	if (u1 != '0' and u1 != null) {w .= ' and id_user=' . u1;};
//	o = 'rpath, cognome, nome';
//	$s='select ' . c . ' from  ' . f . ' where ' . w . ' order by ' . o;
	$c = "ID_USER, LDAP_UID, COGNOME || ' ' || NOME CGN, MAIL, TEL, KR || ' ' || DR RUOLO, KA || ' ' || DA AFFERENZA, KU || ' ' || DU UBICAZIONE, RPATH, (length(RPATH) / 7) LIVELLO";
	$f = 'C##SSS_IMPORT.AB_CAN can,C##SSS_TABLES.FW01_USER u';
	$w = 'can.LDAP_UID = u.USER_NAME(+)';
	if (!empty($u1)) {$w .= " and user_name='" . $u1 . "'";};
	$o = 'rpath, cognome, nome';
	$s='select ' . $c . ' from  ' . $f . ' where ' . $w . ' order by ' . $o;
	$asql['or_organigramma']=$s;

	if (!empty($_REQUEST['od'])){ // prepara per il server CINECA
		foreach ($asql as $key => $value) {			
			$asql[$key]=implode('EAPP_SSSUP_PROD',explode('C##SSS_TABLES',implode('',explode('C##SSS_IMPORT.',implode("SIAIE_SSSUP_PROD.V_IE_PJ_CONTESTI_UTENTE",explode("C##SSS_TABLES.V_IE_PJ_CONTESTI_UTENTE",$value))))));
		}
	}
	$_SESSION['IAM']['asql_pu_ugov']=$asql;
	return $asql;
}
function visute_pu(){					// visualizza gli utenti di un gruppo, profilo, ruolo
	global $conn_new;
	$tit='<h2>'.$_REQUEST['title'].'</h2>';
	$msg='';
	// {"func":"visute_pu","obj_val":"","title":"visualizza utenti del profilo","class":"btn btn-secondary btn-sm","act":"visute_pu","tt0":"ce","tt1":"profili","dom":"mm","k":"DI0005"}
	$msg=json_encode($_REQUEST);
	$msg='';
	switch ($_REQUEST['tt1']) {
		case 'gruppi':	
			$sql0="select * from (".$_SESSION['IAM']['asql_pu_ugov']['ce_gruppi'].") where ID_GRP='".$_REQUEST['k']."'";
			// ID_GRP, ID_USER, USER_NAME, DS_USER, DT_FINE_VAL, DT_INIZIO_VAL, EMAIL, DS_GRUPPO, NOME_GRUPPO, COGNOME, NOME, LKA, LKR, FINE_RAPPORTO, CF, K, KR, KA, KI, DT_RAP_FIN
			$sql="select distinct user_name, case when cognome is null then ds_user else  cognome || ' ' || nome end cn, cf, dr, da, to_char(dt_rap_fin,'dd/mm/yyyy') dt_rap_fin from C##SSS_IMPORT.USR_GRP_EX WHERE ID_GRP='".$_REQUEST['k']."' order by cn"; 
			break;
		case 'profili':	
			$sql0="select * from (".$_SESSION['IAM']['asql_pu_ugov']['ce_profili'].") where CD_PROFILO='".$_REQUEST['k']."'";
			// ID_DIRITTI_PROFILI, ID_USER, ID_GRP, CD_PROFILO, USER_NAME, DS_USER, DT_FINE_VAL, DT_INIZIO_VAL, EMAIL, DS_GRUPPO, NOME_GRUPPO, CD_TIPO_PROFILO, DS_PROFILO, NOME_PROFILO, COGNOME, NOME, LKA, LKR, FINE_RAPPORTO, CF, K, KR, KA, KI, DT_RAP_FIN
			$sql="select distinct user_name, case when cognome is null then ds_user else  cognome || ' ' || nome end cn, cf, dr, da, to_char(dt_rap_fin,'dd/mm/yyyy') dt_rap_fin from C##SSS_IMPORT.USR_GRP_PROFILI_EX WHERE CD_PROFILO='".$_REQUEST['k']."' order by cn"; 
			break;
		case 'ruoli':	
			$sql0="select * from (".$_SESSION['IAM']['asql_pu_ugov']['ce_ruoli'].") where ID_RUOLO='".$_REQUEST['k']."'";
			// ADM, ID_USER, CODFIS, DS_USER, EMAIL, DT_INIZIO_VAL, DT_FINE_VAL, NOME, COGNOME, LDAP_UID, USER_NAME, CD_AREA, DS_AREA, ID_MODULO, CD_MODULO, DS_MODULO, ID_FUNZ, CD_FUNZ, DS_FUNZ, ID_RUOLO, DN_RUOLO, DS_RUOLO, ID_GRP, DS_GRUPPO, CD_PROFILO, DS_PROFILO
			$sql="select distinct user_name, case when cognome is null then ds_user else  cognome || ' ' || nome end cn, CODFIS cf, dr, da, to_char(dt_rap_fin,'dd/mm/yyyy') dt_rap_fin from C##SSS_IMPORT.AB_UTENZE_UGOV_DIRITTI_EX WHERE ID_RUOLO='".$_REQUEST['k']."' order by cn"; 
			break;
		default:		
			$sql0='';
			$sql='';
			break;
	}	
	// get_table_data($a,$g,$d,$n,$t='o',$dtb=true)
	if (!empty($sql0)){
		$a=load_db($conn_new,$sql0,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$msg.=get_alert($_REQUEST['tt1'],'info').'<div class="alert-secondary">'.get_table_data($a[2],'','','tab_gpr','o',false).'</div>';
		}
	}
	if (!empty($sql)){
		$a=load_db($conn_new,$sql,'o',9);
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$msg.=get_alert('utenti','info').'<div>'.get_table_data($a[2],'','','tab_user','o',false).'</div>';
		}
	}
	$stl='bg-info text-center text-white';
	$btn='';
	$domf='';
	$domc='';
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn, 'domf' => $domf, 'domc' => $domc);
	return json_encode($r);		
}
function visruo_pu(){					// visualizza i ruoli di un gruppo, profilo
	global $conn_new;
	$tit='<h2>'.$_REQUEST['title'].'</h2>';
	// $msg='';
	// $msg=json_encode($_REQUEST);
	// ADM, ID_USER, CODFIS, DS_USER, EMAIL, DT_INIZIO_VAL, DT_FINE_VAL, NOME, COGNOME, LDAP_UID, USER_NAME, KR, DR, KA, DA, KI, DI, KU, DU, RPATH, DT_RAP_FIN, CD_AREA, DS_AREA, ID_MODULO, CD_MODULO, DS_MODULO, ID_FUNZ, CD_FUNZ, DS_FUNZ, ID_RUOLO, DN_RUOLO, DS_RUOLO, ID_GRP, DS_GRUPPO, CD_PROFILO, DS_PROFILO
	$msg='';
	switch ($_REQUEST['tt1']) {
		case 'gruppi':	
			$sql0="select * from (".$_SESSION['IAM']['asql_pu_ugov']['ce_gruppi'].") where ID_GRP='".$_REQUEST['k']."'";
			$sql="select distinct CD_AREA, DS_AREA, ID_MODULO, CD_MODULO, DS_MODULO, ID_FUNZ, CD_FUNZ, DS_FUNZ, ID_RUOLO, DN_RUOLO, DS_RUOLO from C##SSS_IMPORT.AB_UTENZE_UGOV_DIRITTI_EX WHERE ID_GRP='".$_REQUEST['k']."' order by DS_AREA, DS_MODULO, DS_FUNZ, DS_RUOLO"; 
			break;
		case 'profili':	
			$sql0="select * from (".$_SESSION['IAM']['asql_pu_ugov']['ce_profili'].") where CD_PROFILO='".$_REQUEST['k']."'";
			$sql="select distinct CD_AREA, DS_AREA, ID_MODULO, CD_MODULO, DS_MODULO, ID_FUNZ, CD_FUNZ, DS_FUNZ, ID_RUOLO, DN_RUOLO, DS_RUOLO from C##SSS_IMPORT.AB_UTENZE_UGOV_DIRITTI_EX WHERE CD_PROFILO='".$_REQUEST['k']."' order by DS_AREA, DS_MODULO, DS_FUNZ, DS_RUOLO"; 
			break;
		default:		
			$sql0='';
			$sql='';
			break;
	}	
	if (!empty($sql0)){
		$a=load_db($conn_new,$sql0,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$msg.=get_alert($_REQUEST['tt1'],'info').'<div class="alert-secondary">'.get_table_data($a[2],'','','tab_gpr_pro','o',false).'</div>';
		}
	}
	if (!empty($sql)){
		$a=load_db($conn_new,$sql,'o',9);
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$msg.=get_alert('ruoli','info').'<div>'.get_table_data($a[2],'','','tab_ruoli','o',false).'</div>';
		}
	}
	$stl='bg-info text-center text-white';
	$btn='';
	$domf='';
	$domc='';
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn, 'domf' => $domf, 'domc' => $domc);
	return json_encode($r);		
}
function agg_pu(){ 						// aggiorna le viste necessarie alla ricerca
	global $conn_new;
	$sql="BEGIN C##SSS_IMPORT.AB_AGGIORNA_LOG_FRONTIERA; COMMIT; END;";
	$parsed = oci_parse($conn_new, $sql);		
	$r = oci_execute($parsed); 					
	$oe = oci_error($parsed);
	
	$a=array();
	if (is_array($oe)){
		$msg=$sql.'<br /><pre>'.print_r($oe,true).'</pre>';
		tolog($msg);
		$a['msg']=get_alert($msg,'danger');
	} else {
		// aggiorna la lista degli utenti UGOV (dipende dalle viste aggiornate)
		$sql="select * from c##sss_import.AB_UTENTI_UGOV_ML";	
		$x=load_db($conn_new,$sql,'o');
		if ($x[0]>0 and $x[1]>0) {	// almeno una riga e almeno una colonna
			$_SESSION['IAM']['utenti_ugov']=$x[2];
		}
		$ss1='<option data-tokens="0" value="0"></option>';
		$ss2='<option data-tokens="0" value="0"></option>';
		$uu=$_SESSION['IAM']['utenti_ugov'];
		for ($j=0; $j < count($uu['LDAP_UID']); $j++) {
			if ($uu['LDAP_UID'][$j]!=''){
				$ss1.='<option data-tokens="'.$uu['LDAP_UID'][$j].'" value="'.$uu['LDAP_UID'][$j].'"';
				if (!empty($_REQUEST['ugov1'])){
					if ($_REQUEST['ugov1'] == $uu['LDAP_UID'][$j]){$ss1.=' selected';}
				}
				$ss1.='>'.$uu['COGNOME'][$j].' '.$uu['NOME'][$j].'</option>';
				$ss2.='<option data-tokens="'.$uu['LDAP_UID'][$j].'" value="'.$uu['LDAP_UID'][$j].'"';
				if (!empty($_REQUEST['ugov2'])){
					if ($_REQUEST['ugov2'] == $uu['LDAP_UID'][$j]){$ss2.=' selected';}
				}
				$ss2.='>'.$uu['COGNOME'][$j].' '.$uu['NOME'][$j].'</option>';
			}
		}
		$s1='<select name="ugov1" id="ugov1" class="form-control form-control-sm" data-live-search="true" data-size="8" sep >
			'.$ss1.'
		</select>';
		$s2='<select name="ugov1" id="ugov1" class="form-control form-control-sm" data-live-search="true" data-size="8" sep >
			'.$ss2.'
		</select>';

		$a['dom']='mm';
		$a['tit']='Aggiorna permessi UGOV';
		$a['domm'][0]['domf']='col_ugov1'; $a['domm'][0]['domc']=$s1;
		$a['domm'][1]['domf']='col_ugov2'; $a['domm'][1]['domc']=$s2;
		$a['msg']=get_alert('Aggiornamento completato','success');
		
		// $msg='Aggiornamento completato';
		// $s=get_alert($msg,'success');
	}
	// return $s;

	// $r=array('tit' => $tit, 'msg' => $msg);
	return json_encode($a);		

}
// --- attivita ---
function get_attivita(){
	global $conn_new;
	$s='';
	// --- lettura attività
	$ta='tutte le attivit&agrave;';
	$sql="select ATTIVITA, DESCR, BREVE, to_char(DT_INS,'YYYY/MM/DD HH24:MI:SS') DT_INS, F_ATTIVO, NOTA from C##SSS_IMPORT.AB_ATTIVITA_SSS";
	if (!empty($_REQUEST['tipo_attivita'])){if ($_REQUEST['tipo_attivita']=='u'){
		$ta='attivit&agrave; usate';
		$sql.=' where attivita in (select distinct attivita from C##SSS_TABLES.V_IE_RU_SGE)';
	}}
	$sql.=' order by attivita';
	$a=load_db($conn_new,$sql,'o');
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		$s.='<br />'.get_alert($ta,'info').'<div>'.get_table_data($a[2],'','','lista_attivita').'</div>';
	} else {
		$s.=get_alert('Impossibile leggere le attivit&agrave; valide per le carriere UGOV','danger');
	}
	return $s;
}
function get_toggle_attivita(){
	global $conn_new;
	$s='';
	if (isset($_REQUEST['conferma'])){
		// esegui toggle
		$att=$_REQUEST['attivita'];
		$set=$_REQUEST['set'];
		$sql="update C##SSS_IMPORT.AB_ATTIVITA_SSS set F_ATTIVO='".$set."' where attivita='".$att."'";
		$a=load_db($conn_new,$sql,'o');
		if ($set=='1'){
			$tit='<h2>Conferma attivazione</h2>';
			$msg='L\' attivazione dell\'attivit&agrave; '.$att.' &egrave; stata eseguita !';
		} else {
			$tit='<h2>Conferma disattivazione</h2>';
			$msg='La disattivazione dell\'attivit&agrave; '.$att.' &egrave; stata eseguita !';
		}
		// $msg.='<br />'.json_encode($_REQUEST);
		$stl='bg-success text-center text-white';
		$btn='';
		$domf='ATT_'.$att.'_F_ATTIVO';
		$domc=$set;
	} else {
		// {"func":"tg_att","obj_val":"","title":"toggle","class":"btn btn-secondary btn-sm btn-block","act":"tg_att","attivita":"1026","dom":"mm","gett":"ATT_1026_F_ATTIVO","ATT_1026_F_ATTIVO":"1"}
		$att=$_REQUEST['attivita'];
		$set='1'; 
		if ($_REQUEST['ATT_'.$att.'_F_ATTIVO']=='1'){$set='0';}
		if ($set=='0'){
			$tit='<h2>Disattiva attivit&agrave;</h2>';
			$msg='Confermi la disattivazione dell\'attivit&agrave; '.$att.' ?';
		} else {
			$tit='<h2>Attiva attivit&agrave;</h2>';
			$msg='Confermi l\'attivazione dell\'attivit&agrave; '.$att.' ?';
		}
		// $msg.='<br />'.json_encode($_REQUEST);
		$stl='bg-primary text-center text-white';
		$btn='<button id="modal-ok" class="btn btn-success btn-block" data-dismiss="modal" act="tg_att" dom="mm" conferma="y" attivita="'.$att.'" set="'.$set.'">Conferma '.(($set=='1')?'':'dis').'attivazione</button><button id="modal-ko" class="btn btn-dark btn-block" data-dismiss="modal">Annulla</button>';
		$domf='';
		$domc='';
	}
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn, 'domf' => $domf, 'domc' => $domc);
	$s=json_encode($r);			
	return $s;	
}
function aggiorna_attivita(){
	global $conn_new;
	$s='';

	// --- controllo attività
	$sql="INSERT INTO C##SSS_IMPORT.AB_ATTIVITA_SSS (ATTIVITA, DESCR, BREVE,DT_INS, F_ATTIVO, NOTA) select augov.ATTIVITA, augov.DESCR, augov.BREVE, sysdate dt_ins, 1 f_attivo, null nota from C##SSS_TABLES.ATTIVITA augov, C##SSS_IMPORT.AB_ATTIVITA_SSS ame where augov.ATTIVITA = ame.ATTIVITA(+) and ame.ATTIVITA is null";	
	$a=load_db($conn_new,$sql,'o');

	// --- leggo le attività aggiunte oggi
	$sql="select * from C##SSS_IMPORT.AB_ATTIVITA_SSS where trunc(DT_INS) = trunc(sysdate) order by attivita";
	$a=load_db($conn_new,$sql,'o');
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		$s.='<br />'.get_table_data($a[2],'','','lista_attivita');
	} else {
		$s.=get_alert('Nessuna attivit&agrave; aggiunta oggi','danger');
	}
	return $s;
}
// --- ict ---
function get_ict_adt(){ 				// allievi da trasferire
	global $conn_new;
	$s='';
	$campi="CMG_UID,COGNOME,NOME,SPAZIO,OCCUPATO,ULTIMO_ACCESSO,REPLACE(ALIASES, ',', ', ') ALIASES,CLASSE,MLOWN,REPLACE(ML, ',', ', ') ML,REPLACE(GRUPPI, ',', ', ') GRUPPI,DATA_AGGIORNAMENTO,CF,KR,DR,KA,DA,SCADENZA_LDAP";
	$sql="SELECT $campi FROM C##SSS_IMPORT.V_CMG_EX_ALLIEVI";
	$w="";
	if (!empty($_REQUEST['ict_dada'])){
		$dada=substr($_REQUEST['ict_dada'],6).'/'.substr($_REQUEST['ict_dada'],3,2).'/'.substr($_REQUEST['ict_dada'],0,2);
		$w.=" AND TO_CHAR (TO_DATE (SUBSTR (A.SCADENZATEMPO, 1, 8), 'yyyymmdd'),'yyyy/mm/dd') >= '".$dada."'";
	}
	if (!empty($_REQUEST['ict_ada'])){
		$ada=substr($_REQUEST['ict_ada'],6).'/'.substr($_REQUEST['ict_ada'],3,2).'/'.substr($_REQUEST['ict_ada'],0,2);
		$w.=" AND TO_CHAR (TO_DATE (SUBSTR (A.SCADENZATEMPO, 1, 8), 'yyyymmdd'),'yyyy/mm/dd') <= '".$ada."'";
	}
	$sql="SELECT b.CMG_UID, b.COGNOME, b.NOME, b.SPAZIO, b.OCCUPATO, b.ULTIMO_ACCESSO, REPLACE(b.ALIASES, ',', ', ') ALIASES, b.CLASSE, b.MLOWN, REPLACE(b.ML, ',', ', ') ML, REPLACE(b.GRUPPI, ',', ', ') GRUPPI, b.DATA_AGGIORNAMENTO, A.LDAP_UID, a.CF, A.BUSINESSCATEGORY kr, A.EMPLOYEETYPE dr, A.DEPARTMENTNUMBER ka, A.PHYSICALDELIVERYOFFICENAME da, TO_CHAR (TO_DATE (SUBSTR (A.SCADENZATEMPO, 1, 8), 'yyyymmdd'),'yyyy/mm/dd') scadenza_ldap
	FROM C##SSS_IMPORT.CMG_BKP b, (SELECT * FROM C##SSS_IMPORT.ANAGRAFICHE_DA_LDAP) a
	WHERE b.CMG_UID = a.LDAP_UID(+) AND UPPER (NVL (B.CLASSE, '')) NOT IN ('GENERICO', 'MIGRATIGEN') AND B.CMG_UID NOT IN (SELECT DISTINCT ldap_uid FROM C##SSS_IMPORT.ab_can WHERE ldap_uid IS NOT NULL) AND (B.CMG_UID IN (SELECT DISTINCT ldap_uid FROM C##SSS_IMPORT.ANAGRAFICHE_DA_LDAP WHERE CF IN (SELECT DISTINCT COD_FIS FROM C##SSS_IMPORT.ELENATT_TOT WHERE TIPO_CORSO_COD IN ('CO1L','CO2L','COCU5','COCU6','D2','D226'))) OR B.CMG_UID IN (SELECT DISTINCT USER_NAME FROM C##SSS_IMPORT.EX_ALLIEVI WHERE USER_NAME IS NOT NULL)) $w
	ORDER BY b.COGNOME, b.NOME";
	
	$a=load_db($conn_new,$sql,'o');
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		$g=[['CMG_UID','CF'],['COGNOME','NOME'],['SPAZIO','OCCUPATO','ULTIMO_ACCESSO'],['CLASSE','MLOWN'],['ALIASES','ML','GRUPPI'],['KR','DR'],['KA','DA'],['SCADENZA_LDAP','DATA_AGGIORNAMENTO']];
		$d=['uid cf','cognome nome','spazio occupato ultimo_accesso','classe owner','aliases ml gruppi','ruolo ldap','afferenza ldap','scadenza_ldap dt_agg_cmg'];
		$nt='allievi_da_trasferire';
		$s.=get_alert('<h2>Allievi da trasferire</h2>','info text-center');
		if ($_SESSION['IAM']['uid_login'] == 'a.bongiorni' and $_SESSION['IAM']['devel']==1){$s.=get_alert($sql);}
		// $s.='<br />'.get_table_data($a[2],$g,$d,$nt);
		$s.='<br />'.get_table_data($a[2],'','',$nt);
	} else {
		$s.=get_alert('<h2>Nessun allievo da trasferire</h2>','danger text-center');
	}
	return $s;	
}
function get_ict_ude(){ 				// utenti da eliminare
	global $conn_new;
	$s='';
	$campi="CMG_UID,COGNOME,NOME,SPAZIO,OCCUPATO,ULTIMO_ACCESSO,REPLACE(ALIASES, ',', ', ') ALIASES,CLASSE,MLOWN,REPLACE(ML, ',', ', ') ML,REPLACE(GRUPPI, ',', ', ') GRUPPI,DATA_AGGIORNAMENTO,CF,KR,DR,KA,DA,SCADENZA_LDAP";
	$sql="SELECT $campi FROM C##SSS_IMPORT.V_CMG_NO_EX_ALLIEVI";
	
	$w="";
	if (!empty($_REQUEST['ict_dada'])){
		$dada=substr($_REQUEST['ict_dada'],6).'/'.substr($_REQUEST['ict_dada'],3,2).'/'.substr($_REQUEST['ict_dada'],0,2);
		$w.=" AND TO_CHAR (TO_DATE (SUBSTR (A.SCADENZATEMPO, 1, 8), 'yyyymmdd'),'yyyy/mm/dd') >= '".$dada."'";
	}
	if (!empty($_REQUEST['ict_ada'])){
		$ada=substr($_REQUEST['ict_ada'],6).'/'.substr($_REQUEST['ict_ada'],3,2).'/'.substr($_REQUEST['ict_ada'],0,2);
		$w.=" AND TO_CHAR (TO_DATE (SUBSTR (A.SCADENZATEMPO, 1, 8), 'yyyymmdd'),'yyyy/mm/dd') <= '".$ada."'";
	}
	$sql="SELECT b.CMG_UID, b.COGNOME, b.NOME, b.SPAZIO, b.OCCUPATO, b.ULTIMO_ACCESSO, REPLACE(b.ALIASES, ',', ', ') ALIASES, b.CLASSE, b.MLOWN, REPLACE(b.ML, ',', ', ') ML, REPLACE(b.GRUPPI, ',', ', ') GRUPPI, b.DATA_AGGIORNAMENTO, A.LDAP_UID, a.CF, A.BUSINESSCATEGORY kr, A.EMPLOYEETYPE dr, A.DEPARTMENTNUMBER ka, A.PHYSICALDELIVERYOFFICENAME da, TO_CHAR (TO_DATE (SUBSTR (A.SCADENZATEMPO, 1, 8), 'yyyymmdd'),'yyyy/mm/dd') scadenza_ldap
  FROM C##SSS_IMPORT.CMG_BKP b, (SELECT * FROM C##SSS_IMPORT.ANAGRAFICHE_DA_LDAP) a
  WHERE b.CMG_UID = a.LDAP_UID(+) AND UPPER (NVL (B.CLASSE, '')) NOT IN ('GENERICO', 'MIGRATIGEN') AND B.CMG_UID NOT IN (SELECT DISTINCT ldap_uid FROM C##SSS_IMPORT.ab_can WHERE ldap_uid IS NOT NULL) AND B.CMG_UID NOT IN (SELECT DISTINCT ldap_uid FROM C##SSS_IMPORT.ANAGRAFICHE_DA_LDAP WHERE CF IN (SELECT DISTINCT COD_FIS FROM C##SSS_IMPORT.ELENATT_TOT WHERE TIPO_CORSO_COD IN ('CO1L','CO2L','COCU5','COCU6','D2','D226'))) AND B.CMG_UID NOT IN (SELECT DISTINCT USER_NAME FROM C##SSS_IMPORT.EX_ALLIEVI WHERE USER_NAME IS NOT NULL) $w
  ORDER BY b.COGNOME, b.NOME";
	$a=load_db($conn_new,$sql,'o');
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		$g=[['CMG_UID','CF'],['COGNOME','NOME'],['SPAZIO','OCCUPATO','ULTIMO_ACCESSO'],['CLASSE','MLOWN'],['ALIASES','ML','GRUPPI'],['KR','DR'],['KA','DA'],['SCADENZA_LDAP','DATA_AGGIORNAMENTO']];
		$d=['uid cf','cognome nome','spazio occupato ultimo_accesso','classe owner','aliases ml gruppi','ruolo ldap','afferenza ldap','scadenza_ldap dt_agg_cmg'];
		$nt='utenti_da_eliminare';
		$s.=get_alert('<h2>Utenti da eliminare</h2>','info text-center');
		if ($_SESSION['IAM']['uid_login'] == 'a.bongiorni' and $_SESSION['IAM']['devel']==1){$s.=get_alert($sql);}
		// $s.='<br />'.get_table_data($a[2],$g,$d,$nt);
		$s.='<br />'.get_table_data($a[2],'','',$nt);
	} else {
		$s.=get_alert('<h2>Nessun utente da eliminare</h2>','danger text-center');
	}
	return $s;	
}
/*
function AllineaCMG($livello=0){
	// aggiorna la tabella C##SSS_IMPORT.CMG_BKP
	// livello: 0 = minimale (inserisce e cancella non modifica) , >0 = completo (inserisce, cancella e modifica)
	global $conn_new;
	$tabella='CMG_BKP';
	$s='';
	
	// --- lista ldap_uid esistenti in communigate
	$alan=0;
	$result = '';
	$msg='';
	try {
		$result = shell_exec('/usr/bin/perl /var/www/html/rac/AB.pl ABListAccounts ABJson ABBase');
		//tolog('ListAccounts: '."\n".$result);
		if ($result != ''){
			$ala=json_decode($result, true); 	// array list account
			$alan = count($ala['UID']);				// numero 
		}
	} catch(Exception $e) {
		$msg='ListAccounts: ' .$e->getMessage();
		tolog($msg);
		$s.=$msg;
		// if (!empty(ob_get_level())){flush(); ob_flush();}
		$errore.='<br />'.$e->getMessage();
	}
	if ($alan <= 0 or $msg!=''){
		$msg='ERROR ListAccounts: ha restituito 0 righe '."\n";
 		tolog($msg);
		$s.=$msg;
		return;
 	}	else {
		$s.=get_alert('numero account su communigate: '.$alan);
	}
	
	// --- lista ldap_uid esistenti in CMG_BKP
	// backup della tabella attuale (x eventuali errori non blocco)
	try {
		$sql='drop table c##sss_import.CMG_BKP_OLD';
		$parsed = oci_parse($conn_new, $sql);		
		$r = oci_execute($parsed); 					
		$oe = oci_error($parsed);
		if (is_array($oe)){
			$msg='<br />'.$sql.'<br /><pre>'.print_r($oe,true).'</pre>';
			tolog($msg);
			$s.=$msg;
			// if (!empty(ob_get_level())){flush(); ob_flush();}
		}
	} catch (Exception $e) {
	}		

	$sql="create table c##sss_import.CMG_BKP_OLD AS SELECT * from c##sss_import.".$tabella;
	$parsed = oci_parse($conn_new, $sql);		
	$r = oci_execute($parsed); 					
	$oe = oci_error($parsed);
	if (is_array($oe)){
		$msg='<br />Errore nella creazione della tabella di backup<br />'.$sql.'<br /><pre>'.print_r($oe,true).'</pre>';
		tolog($msg);
		$s.=$msg;
		return;
	}

	// rimuovo eventuali duplicati
	$sql="DELETE FROM c##sss_import.".$tabella." WHERE rowid not in (SELECT MAX(rowid) FROM c##sss_import.".$tabella." GROUP BY CMG_UID)";
	$a=load_db($conn_new,$sql,'o',false);
	
	// stato attuale della tabella
	$aatt=array('CMG_UID' => [],'COGNOME' => [],'NOME' => []); // array vuoto
	$sql="select distinct cmg_uid, cognome, nome from c##sss_import.".$tabella;
	// $sql.=" where trunc(data_aggiornamento) = trunc(sysdate)";
	$a=load_db($conn_new,$sql,'o',false);
	if ($a[0]>0 and $a[1]>0 and empty($_SESSION['IAM']['forza'])){
		$aatt=$a[2];	// array dati attuali su CMG_BKP
		$s.=get_alert('numero account su CMG_BKP: '.count($aatt['CMG_UID']));
	} else {
			$msg='<br />Non ci sono dati attuali sulla tabella di copia<br />'.$sql;
			tolog($msg);
			$s.=$msg;
	}

	// --- determino la lista da inserire
	$da_inserire=array_values(array_diff($ala['UID'],$aatt['CMG_UID']));
	sort($da_inserire);
	if (count($da_inserire) > 0){
		// $s.=get_alert('da inserire<br />'.print_r($da_inserire,true),'success');
		$s.=get_alert('numero account in inserimento su CMG_BKP: '.count($da_inserire),'success');
		for ($i = 0; $i < count($da_inserire); $i++) {
			$s.=get_alert($da_inserire[$i],'success',true);
			scrivi_CMG($da_inserire[$i]);
		}
	} else {
		$s.=get_alert('niente da inserire','success');
	}
	// --- determino la lista da cancellare
	$da_cancellare=array_values(array_diff($aatt['CMG_UID'],$ala['UID']));
	sort($da_cancellare);
	if (count($da_cancellare) > 0){
		// $s.=get_alert('da cancellare<br />'.print_r($da_cancellare,true),'success');
		$s.=get_alert('numero account in cancellazione su CMG_BKP: '.count($da_inserire),'danger');
		for ($i = 0; $i < count($da_cancellare); $i++) {
			$s.=get_alert($da_cancellare[$i],'danger',true);
		}
		$sin="'".implode("','",$da_cancellare)."'";
		$sql="DELETE FROM c##sss_import.".$tabella." WHERE CMG_UID in(".$sin.")";
		$a=load_db($conn_new,$sql,'o',false);
	} else {
		$s.=get_alert('niente da cancellare','danger');
	}
	
	// --- determino la lista da aggiornare
	// stato attuale della tabella 
	$aatt=array('CMG_UID' => [],'COGNOME' => [],'NOME' => []); // array vuoto
	$sql="select distinct cmg_uid, cognome, nome, to_char(data_aggiornamento,'dd/mm/yyyy') dag from c##sss_import.".$tabella;
	$sql.=" where trunc(data_aggiornamento) <> trunc(sysdate)";
	$a=load_db($conn_new,$sql,'o',false);
	if ($a[0]>0 and $a[1]>0 and empty($_SESSION['IAM']['forza'])){
		$aatt=$a[2];	// array dati attuali su CMG_BKP
		$s.=get_alert('numero account su CMG_BKP non aggiornati: '.count($aatt['CMG_UID']),'warning');
	} else {
		$msg='Non ci sono dati non aggiornati'."\r\n".$sql;
		tolog($msg);
		$s.=get_alert($msg,'success');
	}
	$da_aggiornare=array_values(array_intersect($ala['UID'],$aatt['CMG_UID']));	
	sort($da_aggiornare);
	if (count($da_aggiornare) > 0){
		// $s.=get_alert('da aggiornare<br />'.print_r($da_aggiornare,true),'success');
		for ($i = 0; $i < count($da_aggiornare); $i++) {
			$iaatt=array_search($da_aggiornare[$i],$aatt['CMG_UID']);
			if ($iaatt !== false) {$agg_cmg=' ('.$aatt['DAG'][$iaatt].')';} else {$agg_cmg='';}
			$s.=get_alert('<strong>'.$da_aggiornare[$i].'</strong>'.$agg_cmg,'warning',true);
			if ($livello > 0){
				scrivi_CMG($da_aggiornare[$i]);
			}
		}
	} else {
		$s.=get_alert('niente da aggiornare','success');
	}

	$sql="select * from c##sss_import.cmg_bkp";	
	$a=load_db($conn_new,$sql,'o');
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		$_SESSION['IAM']['cmg_bkp']=$a[2];
	}

	return $s;
}
function scrivi_CMG($uid){
	global $conn_new;
	$tabella='CMG_BKP';
	
	// se esiste aggiornato ad oggi, ignoro
	$sql="SELECT * FROM c##sss_import.".$tabella." WHERE CMG_UID = '".$uid."' and trunc(data_aggiornamento) = trunc(sysdate)";
	$a=load_db($conn_new,$sql,'o',false);
	if ($a[0]>0 and $a[1]>0){return;}

	try {
		$result = shell_exec('/usr/bin/perl /var/www/html/rac/AB.pl ABGetAccountInfo ABJson '.$uid.' ');
		if ($result != ''){
			$ae=json_decode($result, true);
		} else {
			return;
		}
	} catch(Exception $e) {
		tolog('ERROR GetAccountInfo: '.$uid.' '.$e->getMessage());
		$errore.='<br />'.'ERROR GetAccountInfo: '.$uid.' '.$e->getMessage();
		return;
	}

	$sql="DELETE FROM c##sss_import.".$tabella." WHERE CMG_UID = '".$ae['UID'][0]."'";
	$a=load_db($conn_new,$sql,'o',false);
	
	$sql='insert into c##sss_import.'.$tabella.' (cmg_uid, cognome, nome, spazio, occupato, ultimo_accesso, aliases, classe, mlown, ml, gruppi, data_aggiornamento)';
	$sql.=' values (';
	$sql.="'".$ae['UID'][0]."'";
	$sql.=",'".str_replace("'","''",$ae['COGNOME'][0])."'";
	$sql.=",'".str_replace("'","''",$ae['NOME'][0])."'";

	$o=$ae['SPAZIO'][0];
	if($o=='' or strpos($o, 'HASH') !== false) {$f='null';} else {
		$o1=substr($ae['SPAZIO'][0],-1);
		$f=substr($ae['SPAZIO'][0],0,-1);
		if ($o1=='M') {$f=substr($ae['SPAZIO'][0],0,-1)*1024*1024;};
		if ($o1=='G') {$f=substr($ae['SPAZIO'][0],0,-1)*1024*1024*1024;};
	}
	$sql.=",".$f;

	$o=$ae['OCCUPATO'][0];
	if($o=='-empty-' or $o=='' or strpos($o, 'HASH') !== false) {$f='null';} else {$f=$o;}
	$sql.=",".$f;

	$o=$ae['ULTIMO_ACCESSO'][0];
	if ($o=='-not yet-' or $o=='' or strpos($o, 'HASH') !== false) {
		$f='null';
	} elseif (strlen($o) > 30) {
		$ma=array('jan'=>'01','feb'=>'02','mar'=>'03','apr'=>'04','may'=>'05','jun'=>'06','jul'=>'07','aug'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12');
		$ms=substr(strtolower($o),8,3);
		$m=$ma[$ms];
		if($m==''){$m=$ms;}
		$f="to_date('".substr($o,5,2)."/".$m."/".substr($o,12,4)." ".substr($o,17,2).":".substr($o,20,2).":".substr($o,23,2)."','dd/mm/yyyy HH24:MI:SS')";
	} else {
		$f="to_date('".substr($o,2,2)."/".substr($o,5,2)."/".substr($o,8,4)." ".substr($o,13,2).":".substr($o,16,2).":".substr($o,19,2)."','dd/mm/yyyy HH24:MI:SS')";
	}
	$sql.=",".$f;

	$o=$ae['ALIASES'][0];
	if($o=='' or strpos($o, 'HASH') !== false) {$f='';} else {$f=$o;}
	$sql.=",'".$f."'";

	$sql.=",'".str_replace("'","''",$ae['CLASSE'][0])."'";

	$o=$ae['MLOWN'][0];
	if($o=='' or strpos($o, 'HASH') !== false) {$f='';} else {$f=$o;}
	$sql.=",'".$f."'";

	$o=$ae['ML'][0];
	if($o=='' or strpos($o, 'HASH') !== false) {$f='';} else {$f=$o;}
	$sql.=",'".$f."'";

	$o=$ae['GRUPPI'][0];
	if($o=='' or strpos($o, 'HASH') !== false) {$f='';} else {$f=$o;}
	$sql.=",'".$f."'";

	$sql.=",SYSDATE";
	$sql.=')';
	$a=load_db($conn_new,$sql,'o',false);
}
*/
function get_agg_tabelle(){
	global $conn_new;
	$s=''; $d=''; $g='';
	$campi="k_dblink, tmp, schema_ori, tabella_ori, tabella_target, nota, err_ulte, to_char(dt_ulte, 'YYYY/MM/DD HH24:MI:SS') dt_ulte, to_char(dt_ulte_fin, 'YYYY/MM/DD HH24:MI:SS') dt_ulte_fin, round(( dt_ulte_fin-dt_ulte) * 60 * 60 * 24) secondi_di_esecuzione, to_char(floor((SYSDATE - dt_ulte_fin)*24),'FM0000') || ':' || to_char(round((SYSDATE - dt_ulte_fin)*24*60) - (floor((SYSDATE - dt_ulte_fin)*24)*60),'FM00') terminato_da_min";
	$sql="SELECT $campi from C##SSS_IMPORT.A10_IMPORT_EXPORT where azione='10_I' and ATTIVO=1";
	$a=load_db($conn_new,$sql,'o');
	$nt='aggiornamento_tabelle';
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		// $g=[['CMG_UID','CF'],['COGNOME','NOME'],['SPAZIO','OCCUPATO','ULTIMO_ACCESSO'],['CLASSE','MLOWN'],['ALIASES','ML','GRUPPI'],['KR','DR'],['KA','DA'],['SCADENZA_LDAP','DATA_AGGIORNAMENTO']];
		// $d=['uid cf','cognome nome','spazio occupato ultimo_accesso','classe owner','aliases ml gruppi','ruolo ldap','afferenza ldap','scadenza_ldap dt_agg_cmg'];
		$s.='<div class="row">';
			$s.='<div class="col-sm">';
				$s.='<br /><div class="alert alert-info text-center"><h2>'.ucfirst(str_replace('_',' ',$nt)).' - ora di sistema: '.date("d/m/Y H:i:s").'</h2></div>';
			$s.='</div>';
		$s.='</div>';		
		$s.='<br />'.get_table_data($a[2],$g,$d,$nt);
	} else {
		$s.='<div class="row">';
			$s.='<div class="col-sm">';
				$s.='<br /><div class="alert alert-danger text-center"><h2>Nessun '.ucfirst(str_replace('_',' ',$nt)).' - ora di sistema: '.date("d/m/Y H:i:s").'</h2></div>';
			$s.='</div>';
		$s.='</div>';		
	}
	$aa=array();
	$aa[]=array('tabella_ori'=>'C##SSS_IMPORT.AB_CAN','campo_data'=>'DT_REFRESH');
	$aa[]=array('tabella_ori'=>'C##SSS_IMPORT.AB_CFN','campo_data'=>'DT_REFRESH');
	$aa[]=array('tabella_ori'=>'C##SSS_IMPORT.AB_CSN','campo_data'=>'DT_REFRESH');
	$aa[]=array('tabella_ori'=>'C##SSS_IMPORT.ELENATT_TOT','campo_data'=>'DATE_REBUILD');
	$aa[]=array('tabella_ori'=>'C##SSS_IMPORT.AB_DIDORGSEDI','campo_data'=>'DATE_REBUILD');
	$aa[]=array('tabella_ori'=>'C##SSS_IMPORT.CMG_BKP','campo_data'=>'DATA_AGGIORNAMENTO');
	$aa[]=array('tabella_ori'=>'C##SSS_IMPORT.ANAGRAFICHE_DA_LDAP','campo_data'=>'DT_INS');
	$aa[]=array('tabella_ori'=>'C##SSS_TABLES.V_IE_DI_AF_SYLLABUS','campo_data'=>'DATA_CSISSSUP');
	$aa[]=array('tabella_ori'=>'C##SSS_TABLES.V_IE_DI_TESTI_AF','campo_data'=>'DATA_CSISSSUP');
	$aa[]=array('tabella_ori'=>'C##SSS_TABLES.P18_USER','campo_data'=>'DATA_CSISSSUP');
	for ($i = 0; $i < count($aa); $i++) {
		$sql="select max(to_char(".$aa[$i]['campo_data'].", 'yyyy-mm-dd hh24:mi')) dt from ".$aa[$i]['tabella_ori'];
		// $s.='<br>sql: '.$sql;
		$a=load_db($conn_new,$sql,'o');
		if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
			$aa[$i]['schema_ori']='C##SSS_IMPORT';
			$aa[$i]['data_ultima_esecuzione']=$a[2]['DT'][0];
			$diff = date_diff(date_create(date('Y-m-d H:i')),date_create($a[2]['DT'][0]));
			$ns = $diff->format('%d gg %h:%i');
			$aa[$i]['terminato_da']=$ns;
		}
	}
	// $s.='<br>json: '.json_encode($aa);
	$s.='<br />'.get_table_data($aa,$g,$d,$nt.'_import','m');

	return $s;		
}
function get_log_import($t='A10_LOG'){
	global $conn_new;
	$s=''; $d=''; $g='';
	$campi="id, to_char(dt, 'YYYY/MM/DD HH24:MI:SS') dt, da, d, status, query, nr";
	// $campi="*";
	$sql="SELECT $campi FROM C##SSS_IMPORT.".$t;
	$w='';
	if (empty($_REQUEST['ict_dada']) and empty($_REQUEST['ict_ada'])){
		$w.="to_char(DT,'YYYYMMDD') >= to_char(sysdate - 7, 'YYYYMMDD')";
	}
	if (!empty($_REQUEST['ict_dada'])){
		$dada=substr($_REQUEST['ict_dada'],6).substr($_REQUEST['ict_dada'],3,2).substr($_REQUEST['ict_dada'],0,2);
		if ($w != ''){$w.=' AND ';}
		$w.="to_char(DT,'YYYYMMDD') >= '".$dada."'";
	}
	if (!empty($_REQUEST['ict_ada'])){
		$ada=substr($_REQUEST['ict_ada'],6).substr($_REQUEST['ict_ada'],3,2).substr($_REQUEST['ict_ada'],0,2);
		if ($w != ''){$w.=' AND ';}
		$w.="to_char(DT,'YYYYMMDD') <= '".$ada."'";
	}
	if ($w != ''){$sql.=' WHERE '.$w;}
	$sql.=' order by id';
	$a=load_db($conn_new,$sql,'o');
	$nt='log';
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		// $g=[['CMG_UID','CF'],['COGNOME','NOME'],['SPAZIO','OCCUPATO','ULTIMO_ACCESSO'],['CLASSE','MLOWN'],['ALIASES','ML','GRUPPI'],['KR','DR'],['KA','DA'],['SCADENZA_LDAP','DATA_AGGIORNAMENTO']];
		// $d=['uid cf','cognome nome','spazio occupato ultimo_accesso','classe owner','aliases ml gruppi','ruolo ldap','afferenza ldap','scadenza_ldap dt_agg_cmg'];
		$s.='<div class="row">';
			$s.='<div class="col-sm">';
				$s.='<br /><div class="alert alert-info text-center"><h2>'.ucfirst(str_replace('_',' ',$nt)).' - ora di sistema: '.date("d/m/Y H:i:s").'</h2></div>';
			$s.='</div>';
		$s.='</div>';
		$s.='<br />'.get_table_data($a[2],$g,$d,$nt);
	} else {
		$s.='<div class="row">';
			$s.='<div class="col-sm">';
				$s.='<br /><div class="alert alert-danger text-center"><h2>Nessun '.ucfirst(str_replace('_',' ',$nt)).'</h2></div>';
			$s.='</div>';
		$s.='</div>';		
	}
	return $s;		
}
function get_anomalie_ldap(){ 			// anomalie LDAP
	global $conn_new;
	$s='';
	// ricerca simili per cognome e nome (edit distance < 3) con:
	// - nome o cognome o cf o uid diverso
	// - cf o uid null
	$sql="select a.ldap_uid a_uid, gest, cod_fisc, nome, cognome, l.ldap_uid l_uid, cf, givenname, sn, '1' tipo from anagrafiche_da_ldap l, ab_can a where UTL_MATCH.EDIT_DISTANCE (INITCAP (TRIM (sn) || ' ' || TRIM (givenname)),INITCAP (TRIM (cognome) || ' ' || TRIM (nome))) < 3 and (cf is null or a.ldap_uid is null) and (trim(upper(cognome)) <> trim(upper(sn)) or trim(upper(nome)) <> trim(upper(givenname)) or trim(upper(cod_fisc)) <> trim(upper(cf)) or trim(upper(a.ldap_uid)) <> trim(upper(l.ldap_uid)))";
	
	// ricerca simili per cf (edit distance < 3) con:
	// - nome o cognome diverso
	$sql.=" union ";
	$sql.="SELECT a.ldap_uid a_uid, gest, cod_fisc, nome, cognome, l.ldap_uid l_uid, cf, givenname, sn, '2' tipo FROM anagrafiche_da_ldap l, ab_can a WHERE UTL_MATCH.EDIT_DISTANCE (INITCAP (TRIM (cod_fisc)), INITCAP (TRIM (cf))) < 3 AND cf IS NOT NULL and a.ldap_uid IS NOT NULL AND (trim(upper(cognome)) <> trim(upper(sn)) OR trim(upper(nome)) <> trim(upper(givenname)) OR trim(upper(cod_fisc)) <> trim(upper(cf)) OR trim(upper(a.ldap_uid)) <> trim(upper(l.ldap_uid)))";
	
	// ricerca uguali per data nascita (da cf) (edit distance < 3) con:
	// - nome o cognome o cf o uid diverso
	$sql.=" union ";
	$sql.="SELECT a.ldap_uid a_uid, gest, cod_fisc, nome, cognome, l.ldap_uid l_uid, cf, givenname, sn, '3' tipo FROM anagrafiche_da_ldap l, ab_can a WHERE UTL_MATCH.EDIT_DISTANCE (INITCAP (TRIM (sn) || ' ' || TRIM (givenname)),INITCAP (TRIM (cognome) || ' ' || TRIM (nome))) < 3 AND cf IS NOT NULL and a.cod_fisc IS NOT NULL AND (trim(upper(cognome)) <> trim(upper(sn)) OR trim(upper(nome)) <> trim(upper(givenname)) OR trim(upper(cod_fisc)) <> trim(upper(cf)) OR trim(upper(a.ldap_uid)) <> trim(upper(l.ldap_uid))) and substr( trim( cf), 7, 5) = substr( trim( a.cod_fisc), 7, 5)";

	// ricerca uguali per data nascita e città (da cf) (edit distance < 7) con:
	// - nome o cognome o cf o uid diverso
	$sql.=" union ";
	$sql.="SELECT a.ldap_uid a_uid, gest, cod_fisc, nome, cognome, l.ldap_uid l_uid, cf, givenname, sn, '4' tipo FROM anagrafiche_da_ldap l, ab_can a WHERE (UTL_MATCH.EDIT_DISTANCE ( INITCAP (TRIM (sn)) ,INITCAP (TRIM (cognome))) < 7 or UTL_MATCH.EDIT_DISTANCE ( INITCAP (TRIM (givenname)) ,INITCAP (TRIM (nome))) < 7) AND cf IS NOT NULL AND a.cod_fisc IS NOT NULL AND (TRIM (UPPER (cognome)) <> TRIM (UPPER (sn)) OR TRIM (UPPER (nome)) <> TRIM (UPPER (givenname)) OR TRIM (UPPER (cod_fisc)) <> TRIM (UPPER (cf)) OR TRIM (UPPER (a.ldap_uid)) <> TRIM (UPPER (l.ldap_uid))) AND SUBSTR (TRIM (cf), 7, 9) = SUBSTR (TRIM (a.cod_fisc), 7, 9)";
	
	$sql.="ORDER BY cognome, nome";
	
	$a=load_db($conn_new,$sql,'o');
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		// $g=[['CMG_UID','CF'],['COGNOME','NOME'],['SPAZIO','OCCUPATO','ULTIMO_ACCESSO'],['CLASSE','MLOWN'],['ALIASES','ML','GRUPPI'],['KR','DR'],['KA','DA'],['SCADENZA_LDAP','DATA_AGGIORNAMENTO']];
		// $d=['uid cf','cognome nome','spazio occupato ultimo_accesso','classe owner','aliases ml gruppi','ruolo ldap','afferenza ldap','scadenza_ldap dt_agg_cmg'];
		$nt='anomalie_ldap';
		$s.=get_alert('<h2>Anomalie LDAP</h2>','info');
		if ($_SESSION['IAM']['uid_login'] == 'a.bongiorni' and $_SESSION['IAM']['devel']==1){$s.=get_alert($sql);}
		// $s.='<br />'.get_table_data($a[2],$g,$d,$nt);
		$s.='<br />'.get_table_data($a[2],'','',$nt);
	} else {
		$s.=get_alert('<h2>Nessuna anomalia riscontrata</h2>','danger text-center');
	}
	return $s;	
}
function get_anomalie_dett(){
	global $conn_new;
	$s=json_encode($_REQUEST);

	$s=get_alert('LDAP','success',true);
	$g=[['LDAP_UID','CODFIS'],['COGNOME','NOME'],['UMAIL','TELEPHONENUMBER'],['DEPARTMENTNUMBER','OU'],['PHYSICALDELIVERYOFFICENAME'],['SCHACPERSONALPOSITION','BUSINESSCATEGORY','EMPLOYEETYPE','TITLE'],['CN','DESCRIPTION','SAMBAACCTFLAGS'],['FINE_RAPPORTO']];
	$d=['Uid Codice fiscale','Cognome Nome','Mail Telefono','Afferenza Organizzativa','Sede','Ruolo','cn Descrizione Samba','Fine Rapporto'];
	$campiLDAP="LDAP_UID, UIDNUMBER, SN COGNOME, GIVENNAME NOME, MAIL UMAIL, TITLE, TELEPHONENUMBER, CF CODFIS, EMPLOYEENUMBER, DEPARTMENTNUMBER, OU, PHYSICALDELIVERYOFFICENAME, BUSINESSCATEGORY, SCHACPERSONALPOSITION, ALIASEDOBJECTNAME, DESCRIPTION, EMPLOYEETYPE, SCADENZATEMPO, SAMBAACCTFLAGS, CN, TO_CHAR(TO_DATE (SUBSTR (NVL (SCADENZATEMPO, '19000101'), 1,8), 'yyyymmdd'),'dd/mm/yyyy') FINE_RAPPORTO";
	if (!empty($_REQUEST['luid'])){$sw="LDAP_UID='".$_REQUEST['luid']."'";} else {$sw="LDAP_UID is null";}
	if (!empty($_REQUEST['lcf'])){$sw.=" and CF='".$_REQUEST['lcf']."'";} else {$sw.=" and CF is null";}
	$sql = "SELECT ".$campiLDAP." FROM c##sss_import.anagrafiche_da_ldap WHERE (".$sw.") ORDER BY SN, GIVENNAME";
	$a=load_db($conn_new,$sql,'o',false);
	if ($a[0]>0 and $a[1]>0) {	// almeno una colonna e almeno una riga
		$s.='<br />'.get_table_data($a[2],$g,$d,'anomalie_dett_ldap');
	}

	$s.=get_alert('AB_CAN','success',true);
	$g=[['LDAP_UID','COD_FISC'],['COGNOME','NOME'],['MAIL','TEL','CELL'],['GENERE','DT_NASCITA'],['KA','DA'],['KU','DU'],['KR','DR'],['KI','DI'],['KS','DS'],['INIZIO_RAPPORTO','FINE_RAPPORTO'],['NOTA']];
	$d=['Uid Codice fiscale','Cognome Nome','Mail Telefono Cellulare','Genere Data Nascita','Afferenza Organizzativa','Sede','Ruolo','Profilo','Settore','Inizio Fine Rapporto','Nota'];
	$campiCARRIERE="LDAP_UID,K MATRICOLA,COGNOME,NOME,COD_FISC,MAIL,TEL,CELL,GENERE,TO_CHAR(DT_NASCITA,'dd/mm/yyyy') DT_NASCITA,KA,DA,KU,DU,KR,DR,KI,DI,KS,DS,TO_CHAR(DT_RAP_INI,'dd/mm/yyyy') INIZIO_RAPPORTO,TO_CHAR(DT_RAP_FIN,'dd/mm/yyyy') FINE_RAPPORTO, case when NOTA is null then TR else NOTA end NOTA";
	if (!empty($_REQUEST['auid'])){$sw="LDAP_UID='".$_REQUEST['auid']."'";} else {$sw="LDAP_UID is null";}
	if (!empty($_REQUEST['acf'])){$sw.=" and COD_FISC='".$_REQUEST['acf']."'";} else {$sw.=" and COD_FISC is null";}
	$sql = "SELECT ".$campiCARRIERE." FROM c##sss_import.ab_can WHERE (".$sw.") ORDER BY COGNOME, NOME";
	$a=load_db($conn_new,$sql,'o',false);
	if ($a[0]>0 and $a[1]>0) {	// almeno una colonna e almeno una riga
		$s.='<br />'.get_table_data($a[2],$g,$d,'anomalie_dett_ab_can');
	}					
	
	$tit='<h2>Dettaglio possibile anomalia</h2>';
	$msg=$s;
	$stl='bg-primary text-center text-white';
	$btn='';
	$domf='';
	$domc='';
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn, 'domf' => $domf, 'domc' => $domc);
	$s=json_encode($r);		
	return $s;
}
function get_anomalie_ab_can_ldap(){ 	// anomalie LDAP
	global $conn_new;
	$s='';
	$sql="select ldap_uid, mail, ab_can.gest, nome, cognome, cod_fisc, mail_esterna, ka || ' ' || da afferenza, kr || ' ' || dr ruolo, ki || ' ' || di inquadramento, to_char(dt_rap_fin,'dd/mm/yyyy') dt_rap_fin, nota";
	$sql.=" from ab_can, ab_ruolo_new";
	$sql.=" where ab_can.kr = ab_ruolo_new.k_ruolo(+) and nvl(ab_ruolo_new.fl_ldap,0) = 1 and ab_can.ldap_uid is null";
	if (!empty($_REQUEST['cna'])){
		$f=implode("''",explode("'",strtoupper(trim($_REQUEST['cna']))));
		$sql.=" and (upper(nome) like '%".$f."%' or upper(cognome) like '%".$f."%' or upper(nome || ' ' || cognome) like '%".$f."%' or upper(cognome || ' ' || nome) like '%".$f."%' or upper(cod_fisc) like '%".$f."%')";
	}
	$sql.=" order by kr, cognome, nome";
	// $sql="select ab_can.* from ab_can, ab_ruolo_new where ab_can.kr = ab_ruolo_new.K_RUOLO(+) and nvl(ab_ruolo_new.fl_ldap,0) = 1 and ab_can.ldap_uid is null order by kr, cognome, nome";
	$a=load_db($conn_new,$sql,'o');
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		$nt='anomalie_ab_can_ldap';
		$s.=get_alert('<h2>Anagrafiche non agganciate ad LDAP</h2>','info');
		if ($_SESSION['IAM']['uid_login'] == 'a.bongiorni' and $_SESSION['IAM']['devel']==1){$s.=get_alert($sql);}
		// $s.='<br />'.get_table_data($a[2],$g,$d,$nt);
		$s.='<br />'.get_table_data($a[2],'','',$nt);
	} else {
		$s.=get_alert('<h2>Nessuna Anagrafica non agganciata</h2>','success text-center');
	}
	return $s;	
}
function get_ruoli_non_gestiti(){ 		// ruoli non gestiti in AB_RUOLO_NEW
	global $conn_new;
	$s='';
	$sql="select 
		rn.k_ruolo kr_ruolo_new,rn.ruolo dr_ruolo_new, rn.peso_sssup peso_ruolo_new
		,eu.kr kr_gest,eu.dr dr_gest,eu.g gest
		,can.kra kr_attivo,can.dra dr_attivo 
		,csn.krs kr_scaduto,csn.drs dr_scaduto 
		from 
		c##sss_import.ab_ruolo_new rn
		,	(select distinct g, kr, dr from (
				select distinct 'ESSE3' g, tipo_corso_cod kr, tipo_corso_des dr from c##sss_import.elenatt_tot 
				union
				select distinct 'UGOV' g, ruolo kr, ds_ruolo dr from C##SSS_TABLES.V_IE_RU_SGE 
				union
				select distinct 'AFF' g, ta kr, dta dr from C##SSS_AFFILIATI.periodi_ex 
				union
				select distinct 'ECZN' g, kr, dr from c##sss_import.CARRIERE_ECCEZIONI 
			)
		) eu
		,(select distinct kr kra, dr dra from c##sss_import.ab_can) can
		,(select distinct kr krs, dr drs from c##sss_import.ab_csn) csn
		where eu.kr=rn.k_ruolo(+) -- and rn.k_ruolo is null 
		and eu.kr=can.kra(+)
		and eu.kr=csn.krs(+)
		order by eu.dr";
	$a=load_db($conn_new,$sql,'o');
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		$nt='ruoli_non_gestiti';
		$s.=get_alert('<h2>Ruoli non gestiti in AB_RUOLO_NEW</h2>','info');
		if ($_SESSION['IAM']['uid_login'] == 'a.bongiorni' and $_SESSION['IAM']['devel']==1){$s.=get_alert($sql);}
		$s.='<br />'.get_table_data($a[2],'','',$nt);
	} else {
		$s.=get_alert('<h2>Nessun Ruolo non gestito</h2>','success text-center');
	}
	return $s;	
}
function get_stesso_anno_nascita(){ 	// stesso anno di nascita in AB_CAN
	global $conn_new;
	$s='';
	$sql="select substr(x.cod_fisc,7,9) ddnn, x.* from ab_can x where substr(cod_fisc,7,9) in (select ddnn from (select a.*, substr(a.cod_fisc,7,9) ddnn from ab_can a) group by ddnn having COUNT(*) > 1) order by substr(cod_fisc,7,9), cognome, nome";
	$a=load_db($conn_new,$sql,'o');
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		$nt='stesso_anno_nascita';
		$s.=get_alert('<h2>stessa data e luogo di nascita in AB_CAN</h2>','info');
		if ($_SESSION['IAM']['uid_login'] == 'a.bongiorni' and $_SESSION['IAM']['devel']==1){$s.=get_alert($sql);}
		$s.='<br />'.get_table_data($a[2],'','',$nt);
	} else {
		$s.=get_alert('<h2>Nessun doppio anno di nascita</h2>','success text-center');
	}
	return $s;	
}
function anomalie_caratteri_ldap(){		// ricerca anomalie caratteri in LDAP
	global $conn_new;
	$s='';
	$sql="SELECT ldap_uid, sn, givenname, mail, title, cf, ou, employeetype, substr(scadenzatempo,1,8) scadenzatempo,	sambaacctflags, cn, displayname, oubreve FROM (SELECT c##SSS_IMPORT.ANAGRAFICHE_DA_LDAP.*, REPLACE(TRANSLATE(sn || givenname, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 ', '==============================================================='), '=', '') sngi, REPLACE(TRANSLATE(ou, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,', '================================================================='), '=', '') ouri, REPLACE(TRANSLATE(mail, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789@.', '================================================================'), '=', '') mailri FROM c##SSS_IMPORT.ANAGRAFICHE_DA_LDAP) a WHERE a.sngi IS NOT null or a.ouri is not null or a.mailri is not null order by sn, givenname";
	if ($_SESSION['IAM']['uid_login'] == 'a.bongiorni' and $_SESSION['IAM']['devel']==1){
	// if (true){
		$s.=get_alert($sql);
	}
	$a=load_db($conn_new,$sql,'o');
	if ($a[0]>0 and $a[1]>0) {	// almeno una riga e almeno una colonna
		$nt='anomalie_caratteri_ldap';
		$s.=get_alert('<h2>Anomalie caratteri LDAP (sn || givenname) (ou) (mail)</strong></h2>','info');
		$s.='<br />'.get_table_data($a[2],'','',$nt);
	} else {
		$s.=get_alert('<h2>Nessuna anomalia caratteri in LDAP</h2>','success text-center');
	}
	return $s;		
}
// --- acl ---
function get_leggi_acl(){
	$s='';
	$fnp=explode('.',$_REQUEST['fn']);
	$fne=$fnp[count($fnp) -1];	// estensione
	$dir='';	// $dir='/opt/html/ldap/';
	$url=$dir.$_REQUEST['fn'];
	try {
		$s=file_get_contents($url);
	} catch(throwable $e) {
		$s=json_encode($e).' '.json_encode(error_get_last());
	}
	if ($s==''){$s=get_alert('ERRORE: file vuoto o errore di lettura','danger');}
	return $s;
}
function get_salva_acl(){
	$s='';
	if (empty($_REQUEST['fn']) and !empty($_REQUEST['eFileName'])){$_REQUEST['fn']=$_REQUEST['eFileName'];}
	$fnp=explode('.',$_REQUEST['fn']);
	$fne=$fnp[count($fnp) -1];
	$dir='';	// $dir='/opt/html/ldap/';
	$fn=$_REQUEST['fn'];	// nome del file
	$url=$dir.$fn;
	$c=$_REQUEST['cta'];	// contenuto
	
	$btn='';
	$tit="<h2>salva</h2>";
	$msg='';
	$domf='';	// $dom=$a[0];
	$domc='';	// $domc=get_tab($a[0]);
	if (!empty($c)){
		error_reporting(0);
		error_clear_last();
		
		$j=json_decode($c,true); // trasformo la stringa in array per controllo
		$e=get_json_error();
		if ($e!=''){
			$stl='bg-danger text-center text-white';
			$msg.=get_alert('ERRORE json_decode: <strong>'.$e.'</strong>','danger');
		} else {
			$js=json_encode($j);
			$e=get_json_error();
			if ($e!=''){
				$stl='bg-danger text-center text-white';
				$msg.=get_alert('ERRORE json_encode: <strong>'.$e.'</strong>','danger');
			} else {
				$stl='bg-success text-center text-white';
				$msg.='File: <strong>'.$fn.'</strong><br />'; 
				file_put_contents($url, $c, LOCK_EX);
				$e=error_get_last();
				if ($e!=''){
					$stl='bg-danger text-center text-white';
					$msg.=get_alert('ERRORE file_put_contents: <strong>'.$e.'</strong>','danger');
				}
			}
		}
		error_reporting(E_ALL);
	} else {
		$stl='bg-danger text-center text-white';
		$msg='niente da salvare';
	}
	$r=array('tit' => $tit, 'msg' => $msg, 'stl' => $stl, 'btn' => $btn, 'domf' => $domf, 'domc' => $domc);
	$s=json_encode($r);
	return $s;
}
// ----------------------------------------------------------------------------
// FUNZIONI DI SERVIZIO RIUTILIZZABILI
// ----------------------------------------------------------------------------
function detectEol($str, $default=''){
/**
 * Detects the end-of-line character of a string.
 * @param string $str The string to check.
 * @param string $default Default EOL (if not detected).
 * @return string The detected EOL, or default one.
 */
    static $eols = array(
        "\0x000D000A", // [UNICODE] CR+LF: CR (U+000D) followed by LF (U+000A)
        "\0x000A",     // [UNICODE] LF: Line Feed, U+000A
        "\0x000B",     // [UNICODE] VT: Vertical Tab, U+000B
        "\0x000C",     // [UNICODE] FF: Form Feed, U+000C
        "\0x000D",     // [UNICODE] CR: Carriage Return, U+000D
        "\0x0085",     // [UNICODE] NEL: Next Line, U+0085
        "\0x2028",     // [UNICODE] LS: Line Separator, U+2028
        "\0x2029",     // [UNICODE] PS: Paragraph Separator, U+2029
        "\0x0D0A",     // [ASCII] CR+LF: Windows, TOPS-10, RT-11, CP/M, MP/M, DOS, Atari TOS, OS/2, Symbian OS, Palm OS
        "\0x0A0D",     // [ASCII] LF+CR: BBC Acorn, RISC OS spooled text output.
        "\0x0A",       // [ASCII] LF: Multics, Unix, Unix-like, BeOS, Amiga, RISC OS
        "\0x0D",       // [ASCII] CR: Commodore 8-bit, BBC Acorn, TRS-80, Apple II, Mac OS <=v9, OS-9
        "\0x1E",       // [ASCII] RS: QNX (pre-POSIX)
        //"\0x76",       // [?????] NEWLINE: ZX80, ZX81 [DEPRECATED]
        "\0x15",       // [EBCDEIC] NEL: OS/390, OS/400
    );
    $cur_cnt = 0;
    $cur_eol = $default;
    foreach($eols as $eol){
        if(($count = substr_count($str, $eol)) > $cur_cnt){
            $cur_cnt = $count;
            $cur_eol = $eol;
        }
    }
    return $cur_eol;
}
function url_exists($url) {
    $h = get_headers($url);
	$status = array();
    preg_match('/HTTP\/.* ([0-9]+) .*/', $h[0] , $status);
    return ($status[1] == 200);
}
function check_url($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch , CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    $headers = curl_getinfo($ch);
    curl_close($ch);
    return $headers['http_code'];
}
function ab_check_url($url) {
	$ff = '';
	try {
		$ff = file_get_contents($url);
	} catch(Exception $e) {
		$ff = '';
	}	
	return $ff;
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
function ab_str_utf8_ascii($s){
	$as = str_split($s);
	$t=''; $p=0; $tc='';
	foreach ($as as $c) {
		$n=ord($c);
		if ($n == 32 					// (spazio)
			or $n == 39 				// '
			or ($n > 44 and $n < 58)	// -./0123456789
			or $n == 61 				// =
			or ($n > 64 and $n < 91)	// @AB ... YZ
			or $n == 95 				// _
			or ($n > 96 and $n < 123)	// ab ... yz
		) {
			$t.=$c;
			$p=0;
		} else {
			if ($n==194 or $n==195){
				$p=$n;
			} else {
				// if ($e!=''){$e.=', ';}
				if ($n < 128){
					// $e.=$n.' '.$c;
					$p=0;
				} else {
					if ($p==194 or $p==195){
						$tc = ab_tr_utf8($p,$n);
					}
					// $e.=$p.' '.$n.' ('.$tc.')';
					$t.=$tc;
				}
			}
		}
	}
	return $t;
}
function ab_tr_utf8($p,$n){
	// https://www.utf8-chartable.de/unicode-utf8-table.pl?utf8=dec
	// https://www.man7.org/linux/man-pages/man7/ascii.7.html
	if ($p==194){
		// if ($n==180){return "'";}
		if ($n==180){return;}
	}
	if ($p==195){
		if ($n>=128 and $n<=133){return "A";}
		if ($n==134){return "AE";}
		if ($n==135){return "C";}
		if ($n>=136 and $n<=139){return "E";}
		if ($n>=140 and $n<=143){return "I";}
		if ($n==144){return "D";}
		if ($n==145){return "N";}
		if ($n>=146 and $n<=150){return "O";}
		if ($n==151){return "x";}
		if ($n==152){return "O";}
		if ($n>=153 and $n<=156){return "U";}

		if ($n>=160 and $n<=165){return "a";}
		if ($n==166){return "ae";}
		if ($n==167){return "c";}
		if ($n>=168 and $n<=171){return "e";}
		if ($n>=172 and $n<=175){return "i";}
		if ($n==176){return "o";}
		if ($n==177){return "n";}
		if ($n>=178 and $n<=182){return "o";}
		if ($n==183){return "/";}
		if ($n==184){return "o";}
		if ($n>=185 and $n<=188){return "u";}
	}
	return;
}
function changePasswordAD($user,$newPassword){
	$s='';$e='';
	$debug=0;
	error_reporting(E_ALL);
	$user=strtolower($user);
	if ($debug) echo "<h2>TCP/IP Connection</h2>\n";
	// Get the port for the WWW service. 
	$service_port = '4444';
	// Get the IP address for the target host. 
	$address = '192.168.64.81';
	// Create a TCP/IP socket.
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if ($socket === false) {
		$e.="socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
	} else {
		if ($debug) $s.="OK.\n";
	}

	if ($debug) {$s.="Attempting to connect to '$address' on port '$service_port'...";}
	$result = socket_connect($socket, $address, $service_port);
	if ($result === false) {
		$socketerror=socket_strerror(socket_last_error($socket));
		$e.="C'e' stato un problema col server Active Directory, si prega di contattare l'amministratore di sistema all'indirizzo: <a href='mailto:helpdesk@santannapisa.it'>helpdesk@santannapisa.it</a> <br> <i>Active directory server problem. Please contact your system administrator at <a href='mailto:helpdesk@santannapisa.it'>helpdesk@santannapisa.it</a></i>";
		// sendmail('helpdesk@santannapisa.it','Change password AD','socket_connect() failed. Reason: $socketerror ($result)',"From: CSI Scuola Sant'Anna <helpdesk@santannapisa.it>");
	} else {
		if ($debug) {$s.="AD ok.\n";}
	}
	$user=substr($user,0,20);
	$in = ";Set-ADAccountPassword -identity $user -NewPassword (ConvertTo-SecureString $newPassword -AsPlainText -force) -Reset -PassThru;";
	$in .= "Host: csidev\r\n";
	$in .= "Connection: Close\r\n\r\n";
	$in .= "quit\r\n\r\n";
	$out = '';

	if ($debug) {$s.="Sending HTTP HEAD request...";}
	socket_write($socket, $in, strlen($in));
	if ($debug) {$s.="OK.\n";}

	if ($debug) {$s.="Reading response:\n\n";}
	while ($out = socket_read($socket, 2048)) {
		if ($debug) {$s.=$out;}
	}

	if ($debug) {$s.="Closing socket...";}
	$in = "quit\r\n";
	socket_write($socket, $in, strlen($in));
	//socket_close($socket);
	if ($debug) {$s.="OK.\n\n";}
}
function get_storico_ldap($uid=''){
	global $conn_sl;
	
	$nome='account';
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}	// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}	// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}	// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}	// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict
	
	$s='';
	// if($su){$s.=get_alert(json_encode($_REQUEST));}
	if (empty($uid)){
		$s=get_alert('Non trovato','warning');
		return $s;
	}
	
	$tabella='richieste_account'; $k='ra_uid';
	if (!empty($_REQUEST['title'])){
		if (stripos($_REQUEST['title'],'ALIAS') !== false){$tabella='richieste_account_alias'; $k='raa_uid';}
		if (stripos($_REQUEST['title'],'ALUMNI') !== false){$tabella='richieste_account_alumni'; $k='raal_ldap_uid';}
	}
	
	// storico LDAP
	$j=array();
	$sql="select * from storico_ldap.".$tabella." where lower(".$k.")='".strtolower($uid)."'";
	// if($su){$s.=get_alert($sql);}
	$aa=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){$e.=get_alert(mysqli_error($conn_sl),'danger');}
	if (mysqli_num_rows($aa) > 0) {	// dati trovati
		foreach($aa as $row){
			$j[]=$row;
		}
	}
	if (count($j)>0){	
		$s.='<div class="row py-2">'; // intestazione
			$s.='<div class="col-sm-2 text-center">';
				clearstatcache();
				if (empty($uid)){
					$im='broken.jpg';
				} else {
					$ff='';
					try {
						$ff = file_get_contents("https://dotnetu.local/ms/getfoto.php?toc===AW55EVNdEeBNmaF1zIxIHQsBzU&uid=".$uid);
						$im='data:image/jpeg;base64,'.$ff;
					} catch(Exception $e) {
						// $errore.='<br />'.$e->getMessage();
						$im='broken.jpg';
					}
				}
				// $s.='<img class="img-fluid img-thumbnail" style="max-height: 100px;" src="'.$im.'" />';
				$s.='<img class="img-fluid img-thumbnail" src="'.$im.'" />';
			$s.='</div>';
			$s.='<div class="col-sm-10">';
			
				foreach ($j[0] as $jk => $jv){
					$s.='<div class="row">';
						$s.='<div class="col-sm-3 text-right text-primary"><strong>'.$jk.':</strong></div>';
						$s.='<div class="col-sm-9"><strong>'.htmlentities($jv).'</strong></div>';
					$s.='</div>';
				}
			
			
//				$s.='<div class="row">';
//					$s.='<div class="col-sm-3 text-right text-primary"><strong>Uid e alias:</strong></div>';
//					$s.='<div class="col-sm-9"><strong>'.htmlentities($j[0][$k]).'</strong> '.(empty($j[0]['ra_aliases'])?'':'('.$j[0]['ra_aliases'].')').'</div>';
//				$s.='</div>';
//				if ($tabella=='richieste_account'){
//					$s.='<div class="row">';
//						$s.='<div class="col-sm-3 text-right text-primary"><strong>Codice fiscale:</strong></div>';
//						$s.='<div class="col-sm-9">'.htmlentities($j[0]['ra_cf']).'</div>';
//					$s.='</div>';
//					$s.='<div class="row">';
//						$s.='<div class="col-sm-3 text-right text-primary"><strong>Nome e cognome:</strong></div>';
//						$s.='<div class="col-sm-9"><h4>'.htmlentities($j[0]['ra_nome']).' '.htmlentities($j[0]['ra_cognome']).'</h4></div>';
//					$s.='</div>';
//					$s.='<div class="row">';
//						$s.='<div class="col-sm-3 text-right"><strong>Ruolo:</strong></div>';
//						$i=array_search($j[0]['ra_ruolo'],$_SESSION['IAM']['lista_ruoli']['KR']);
//						if ($i !== false){$dr=$_SESSION['IAM']['lista_ruoli']['DR'][$i];} else {$dr='';}
//						$s.='<div class="col-sm-9">'.htmlentities($j[0]['ra_ruolo']).' - '.htmlentities($dr).'</div>';
//					$s.='</div>';
//					$s.='<div class="row">';
//						$s.='<div class="col-sm-3 text-right"><strong>Afferenza:</strong></div>';
//						$i=array_search($j[0]['ra_afferenza'],$_SESSION['IAM']['lista_afferenze']['KA']);
//						if ($i !== false){$da=$_SESSION['IAM']['lista_afferenze']['DA'][$i];} else {$da='';}
//						$s.='<div class="col-sm-9">'.htmlentities($j[0]['ra_afferenza']).' - '.htmlentities($da).'</div>';
//					$s.='</div>';
//					$s.='<div class="row">';
//						$s.='<div class="col-sm-3 text-right text-primary"><strong>Fine rapporto:</strong></div>';
//						$s.='<div class="col-sm-9">'.$j[0]['ra_fine'].'</div>';
//					$s.='</div>';
//					$s.='<div class="row">';
//						$s.='<div class="col-sm-3 text-right"><strong>Nota:</strong></div>';
//						$s.='<div class="col-sm-9">'.htmlentities($j[0]['ra_note']).'</div>';
//					$s.='</div>';
//					if ($su){
//						$s.='<div class="row">';
//							$s.='<div class="col-sm-3 text-right"><strong>Inserimento:</strong></div>';
//							$s.='<div class="col-sm-9">'.htmlentities($j[0]['ra_usr_ins']).' '.htmlentities($j[0]['ra_dt_ins']).'</div>';
//						$s.='</div>';
//						$s.='<div class="row">';
//							$s.='<div class="col-sm-3 text-right"><strong>Ultima modifica:</strong></div>';
//							$s.='<div class="col-sm-9">'.htmlentities($j[0]['ra_usr_mod']).' '.htmlentities($j[0]['ra_dt_mod']).'</div>';
//						$s.='</div>';
//					}
//				}
			$s.='</div>';
		$s.='</div>';	
	}
	$stl='bg-success text-center text-white';
	$tit='Dettaglio storico LDAP';
	$r=array('tit' => $tit, 'msg' => $s, 'stl' => $stl);
	return json_encode($r);
}
function get_can_d($uid=''){
	$s='';
	if (empty($uid) and empty($_REQUEST['uii'])){
		$s=get_alert('Non trovato','warning');
		return $s;
	}
	if (empty($uid)){$uid=$_REQUEST['uii'];}
	$i=array_search($uid,$_SESSION['IAM']['ab_can']['LDAP_UID']);
	// if (!empty($_SESSION['IAM']['ab_can']['LDAP_UID'][$i])) {	// trovato
	if ($i !== false) {	// trovato
		$s.='<div class="row py-2">'; // intestazione
			$s.='<div class="col-sm-2 text-center">';
				clearstatcache();
				if (empty($uid)){
					$im='broken.gif';
				} else {
					$ff='';
					try {
						$ff = file_get_contents("https://dotnetu.local/ms/getfoto.php?toc===AW55EVNdEeBNmaF1zIxIHQsBzU&uid=".$uid, false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,))));
						if (!empty($ff)){
							$im='data:image/jpeg;base64,'.$ff;
						} else {
							$im='broken.gif';
						}
					} catch(Exception $e) {
						// $errore.='<br />'.$e->getMessage();
						$im='broken.gif';
					}
				}
				// $s.='<img class="img-fluid img-thumbnail" style="max-height: 100px;" src="'.$im.'" />';
				$s.='<img class="img-fluid img-thumbnail" src="'.$im.'" />';
				// $s.='<br />('.url_exists($im).')';
				// $s.='<br />('.check_url($im).')';
				// $s.='<br />('.ab_check_url($im).')';
			$s.='</div>';
			$s.='<div class="col-sm-10">';
				$s.='<div class="row">';
					$s.='<div class="col-sm-3 text-right"><strong>Gestionale:</strong></div>';
					$s.='<div class="col-sm-9">'.$_SESSION['IAM']['ab_can']['GEST'][$i].'</div>';
				$s.='</div>';
				$s.='<div class="row">';
					$s.='<div class="col-sm-3 text-right"><strong>Uid:</strong></div>';
					$s.='<div class="col-sm-9">'.htmlentities($_SESSION['IAM']['ab_can']['LDAP_UID'][$i]).'</div>';
				$s.='</div>';
				$s.='<div class="row">';
					$s.='<div class="col-sm-3 text-right"><strong>Codice fiscale:</strong></div>';
					$s.='<div class="col-sm-9">'.htmlentities($_SESSION['IAM']['ab_can']['COD_FISC'][$i]).'</div>';
				$s.='</div>';
				$s.='<div class="row">';
					$s.='<div class="col-sm-3 text-right"><strong>Mail:</strong></div>';
					$s.='<div class="col-sm-9">'.htmlentities($_SESSION['IAM']['ab_can']['MAIL'][$i]).'</div>';
				$s.='</div>';
				$s.='<div class="row">';								
					$s.='<div class="col-sm-3 text-right"><strong>Nome e cognome:</strong></div>';
					$s.='<div class="col-sm-9"><h4>'.htmlentities($_SESSION['IAM']['ab_can']['NOME'][$i]).' '.htmlentities($_SESSION['IAM']['ab_can']['COGNOME'][$i]).'</h4></div>';
				$s.='</div>';
				$s.='<div class="row">';
					$s.='<div class="col-sm-3 text-right"><strong>Ruolo:</strong></div>';
					$s.='<div class="col-sm-9">'.htmlentities($_SESSION['IAM']['ab_can']['KR'][$i]).' - '.htmlentities($_SESSION['IAM']['ab_can']['DR'][$i]).'</div>';
				$s.='</div>';
				$s.='<div class="row">';
					$s.='<div class="col-sm-3 text-right"><strong>Afferenza:</strong></div>';
					$s.='<div class="col-sm-9">'.htmlentities($_SESSION['IAM']['ab_can']['KA'][$i]).' - '.htmlentities($_SESSION['IAM']['ab_can']['DA'][$i]).'</div>';
				$s.='</div>';
				$s.='<div class="row">';
					$s.='<div class="col-sm-3 text-right"><strong>Fine rapporto:</strong></div>';
					$s.='<div class="col-sm-9">'.$_SESSION['IAM']['ab_can']['DTF'][$i].'</div>';
				$s.='</div>';
				$s.='<div class="row">';
					$s.='<div class="col-sm-3 text-right"><strong>Nota:</strong></div>';
					$s.='<div class="col-sm-9">'.htmlentities($_SESSION['IAM']['ab_can']['NOTA'][$i]).'</div>';
				$s.='</div>';
			$s.='</div>';
		$s.='</div>';
	}	
	return $s;
}
function crea_password($par=array()){
	// esempio: crea_password(array('t'=>10,'u'=>4,'l'=>3,'n'=2,'s'=>1))
	// caratteri per password t=totali, u=Ucase, l=LCase, n=Numeri, s=Special, ss=simple special
	// $y=array('u'=>'ABCDEFGHIJKLMNOPQRSTUVWXYZ','l'=>'abcdefghijklmnopqrstuvwxyz','n'=>'0123456789','s'=>'!?=%*.:;~+-'); // ~ (126)
	$y=array('u'=>'ABCDEFGHIJKLMNOPQRSTUVWXYZ','l'=>'abcdefghijklmnopqrstuvwxyz','n'=>'0123456789','s'=>'!?=%*.:;+-'); 
	$w=array_values($y);
	$z=array_keys($y);
	$ch = implode('',$w);
	$ncp=8;	// numero caratteri password (default 8)
	$s=substr(str_shuffle($ch),0,$ncp); // totalmente casuale
	// se specificato il numero di caratteri per categoria
	if (is_object($par)){
		if (!empty($par['ss'])){if ($par['ss']){
			$y=array('u'=>'ABCDEFGHIJKLMNOPQRSTUVWXYZ','l'=>'abcdefghijklmnopqrstuvwxyz','n'=>'0123456789','s'=>'!?*.:;'); 
			$w=array_values($y);
			$z=array_keys($y);
			$ch = implode('',$w);
		}}
		$s='';
		// controllo se specificato il numero di crt totali (altrimenti rimane ad 8)
		if (!empty($par['t'])){if (is_numeric($par['t'])){$ncp=$par['t'];}}
		$x=$ncp; // deposito numero crt da assegnare
		for ($i=0; $i < count($y); $i++) { 	// loop sulle 4 categorie
			$c=$z[$i];	// categoria
			$sc=$w[$i];	// stringa della categoria $sc=$y[$c];
			if (!empty($par[$c])){		// è stata specificato il n. per la cat.
				$n=$par[$c];			// n. chr per questa categoria
				if (is_numeric($n)){	// è un numero
					if ($n<=$x and $n<=strlen($sc)){ 
						// il numero è < del totale da assegnare e del n. caratteri della categoria
						$s.=substr(str_shuffle($sc),0,$n); 	// pesco $n crt dalla cat.
						$x-=$n;								// sottraggo il numero della cat dal deposito 
						$ch=str_replace($sc, "", $ch);		// tolgo la cat. dalla stringa totale
					}
				}
			}
		}
		// se ho ancora caratteri da assegnare e ne ho a sufficinza nella stringa totale residua, li pesco da li
		if (strlen($s)<$ncp and strlen($ch)>=($ncp-strlen($s))){
			$s.=substr(str_shuffle($ch),0,$x);
		}
		$s=str_shuffle($s); // rimescolo la stringa finale
	}
	return $s;
}
function convert_utf8( $string){
	if ( strlen(utf8_decode($string)) == strlen($string) ) {  
		// $string is not UTF-8
		try {
			return iconv("ISO-8859-1", "UTF-8//TRANSLIT", $string); 
		} catch(Throwable $e) {
			tolog(print_r(error_get_last(),true));
			try {
				return iconv("CP1252", "UTF-8//TRANSLIT", $string); // CP1252 
			} catch(Throwable $e) {
				tolog(print_r(error_get_last(),true));
			}
		}
	} else {
		// already UTF-8
		return $string;
	}
}
function LMHash($Input){
 $Input=iconv('UTF-8','UTF-16LE',$Input);
 $MD4Hash=hash('md4',$Input);
 $NTLMHash=strtoupper($MD4Hash);
 return($NTLMHash);
}
function ldap_authenticate($username,$password,$lad,$dett=0){
	global $ip_ldap, $ip_ad, $ou_ad, $devel, $sviluppo_albo;
	$vuoto=array('count'=>0);

	$nome='account';
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'S')!==false){$su=true;} else {$su=false;}	// superuser
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'R')!==false){$rd=true;} else {$rd=false;}	// lettura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'W')!==false){$wr=true;} else {$wr=false;}	// scrittura
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'U')!==false){$ut=true;} else {$ut=false;}	// utente
	if (strpos($_SESSION['IAM']['my_acl'][$nome],'I')!==false){$ict=true;} else {$ict=false;}	// ict

	// $ip_ldap = '192.168.64.11';
	// $ip_ldap_devel = '192.168.64.6';

// if ($sviluppo_albo){write_log('_tmp.log',"ldap_authenticate - user: $username, password: $password, tipo: $lad");}		

	if (substr($username,0,3)=='im_'){
		$loc_ip_ldap='192.168.64.6';
		$loc_ou_ad="OU=ADprova,DC=sssapisa,DC=it";
		$md='<strong class="text-danger">DEVEL</strong> ';
	} else {
		$loc_ip_ldap='192.168.64.11';
		$loc_ou_ad="OU=OpenLdap,DC=sssapisa,DC=it";
		$md='';
	}

	$o=array();
	$o['autenticato']=0;
	$o['esiste']=0;
	$o['err']='';
	error_reporting(0);
	// set_error_handler('count', 0);
	restore_error_handler();
	// $devel=false;
	if ($lad=='LDAP') {
		try {
			$ds = ldap_connect($loc_ip_ldap,389);
		} catch (Exception $e) {
			$o['err'].=get_alert('ERROR Message: ' .$e->getMessage(),'danger',true);
		}
		if ($ds) {
			ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
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
					&(|(term1)(term2))(!(&(term1)(term2))) 	- matches XOR term1 term2

				// ESEMPI
					$p='(|(uid=a.bongiorni)(uid=a.signorini))';	// OK
					$p='departmentNumber=005730';
					$p='uid=*';
					$result=ldap_search($ds, $base, $p, array($cosa));
			*/		
			$base="ou=Users,o=sss,c=it";
			$p='uid='.$username;	
			$result=ldap_search($ds, $base, $p);
			if ($result!==false){
				$en = ldap_get_entries($ds, $result);
				if (!is_null($en) and $en != $vuoto) {
					$o['esiste']=1;
					$o['dettaglio']=get_dettaglio($en);
					error_reporting(0);
					restore_error_handler();
					try {
						$ldapBind = ldap_bind($ds,'uid='.$username.',ou=Users,o=sss,c=it',"$password");
					} catch (Exception $e) {
						$o['err'].=get_alert('ERROR Message: ' .$e->getMessage(),'danger',true);
					}
					error_reporting(E_ALL);
					set_error_handler("alboError");
					if ($ldapBind) {
						$o['autenticato']=1;
					} else {
						$o['err'].=get_alert('Credenziali di autenticazione (LDAP) errate','danger',true);
					}
				}
			}		
		} else {
			$o['err'].=get_alert('Non posso connettermi al server di autenticazione LDAP','danger',true);
		}
	}
	if ($lad=='LDAP_GUEST') {
		try {
			$ds = ldap_connect($loc_ip_ldap,389);	// 192.168.64.112 = Slave ???
		} catch (Exception $e) {
			$o['err'].=get_alert('ERROR Message: ' .$e->getMessage(),'danger',true);
		}
		if ($ds) {
			ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
			$base="ou=GuestUsers,o=sss,c=it";
			$p='uid='.$username;	
			$result=ldap_search($ds, $base, $p);
			if ($result!==false){
				$en = ldap_get_entries($ds, $result);
				if (!is_null($en) and $en != $vuoto) {
					$o['esiste']=1;
					$o['dettaglio']=get_dettaglio($en);
					error_reporting(0);
					restore_error_handler();
					try {
						$ldapBind = ldap_bind($ds,'uid='.$username.',ou=GuestUsers,o=sss,c=it',"$password");
					} catch (Exception $e) {
						$o['err'].=get_alert('ERROR Message: ' .$e->getMessage(),'danger',true);
					}
					error_reporting(E_ALL);
					set_error_handler("alboError");
					if ($ldapBind) {
						$o['autenticato']=1;
					} else {
						$o['err'].=get_alert('Credenziali di autenticazione (LDAP GUEST) errate','danger',true);
					}
				}
			}		
		} else {
			$o['err'].=get_alert('Non posso connettermi al server di autenticazione LDAP','danger',true);
		}
	}
	if ($lad=='AD') {
		try {
			$ds = ldap_connect($ip_ad,389);
		} catch (Exception $e) {
			$o['err'].=get_alert('ERROR Message: ' .$e->getMessage(),'danger',true);
		}
// if ($sviluppo_albo){write_log('_tmp.log',"ldap_authenticate - dopo ldap_connect (err: ".$o['err'].")");}		
		if ($ds) {
			ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
			// error_reporting(0);
			// restore_error_handler();
			try {
				$ldapBind = ldap_bind($ds,'sssapisa.it/OpenLdap/Servizi ICT/User Read','readuser');	// ok
				$sr = ldap_search($ds, $loc_ou_ad, "(userprincipalname=$username@santannapisa.it)");
			} catch (Exception $e) {
				$o['err'].=get_alert('ERROR Message: ' .$e->getMessage(),'danger',true);
			}
			// error_reporting(E_ALL);
			// set_error_handler("alboError");
			if ($sr) {
				try {
					$en = ldap_get_entries($ds, $sr);
					$nen = ldap_count_entries($ds, $sr);
				} catch (Exception $e) {
					$o['err'].=get_alert('ERROR Message: ' .$e->getMessage(),'danger',true);
				}
				if (!is_null($en) and $en != $vuoto) {
					$o['esiste']=1;
					$o['dettaglio']=get_dettaglio($en);
				}
				for ($i=0; $i<$nen; $i++) {
					$upn=$en[$i]["userprincipalname"][0];
					if ($upn==$username.'@santannapisa.it') {
						$cn=$en[$i]["cn"][0];
						$dn=$en[$i]["dn"];
						// error_reporting(0);
						// restore_error_handler();
						try {
							$ldapBind = ldap_bind($ds,$dn,$password);
						} catch (Exception $e) {
							$o['err'].=get_alert('ERROR Message: ' .$e->getMessage(),'danger',true);
						}
						// error_reporting(E_ALL);
						// set_error_handler("alboError");
						if ($ldapBind) {
							$o['autenticato']=1;
						} else {
							$o['err'].=get_alert('Credenziali di autenticazione (ACTIVE DIRECTORY) errate','danger',true);
							$e=get_ldap_error('AD');
							if ($su){
								if ($e != ''){$o['err'].=get_alert(get_ldap_error('AD'),'danger',true);}
								if($dett > 0){$o['err'].='<br />dn: <strong>'.$dn.'</strong>';}
							}
						}
						break;
					}
				}
			} else {
				$o['err'].=get_alert('Credenziali di ricerca (ACTIVE DIRECTORY) errate','danger',true);
			}
		} else {
			$o['err'].=get_alert('Non posso connettermi al server di autenticazione ACTIVE DIRECTORY','danger',true);
		}
	}
	if ($su and $o['err'] != '' and $dett > 0){$o['err'].='<br />'.$md.' - u: <strong>'.$username.'</strong> - p: <strong>'.$password.'</strong> ';}
	return $o;
}
function get_dettaglio($en){
	$s='<div style="display:none;">';
	$vuoto=array('count'=>0);
	if (!is_null($en) and $en != $vuoto) {
		if (!empty($en[0])){
			if (is_array($en[0])){
				$r0=array();
				$r1=array_keys($en[0]);
				foreach($r1 as $i){
					$ilo=strtolower($i);
					if (!is_numeric($i) and !stripos($ilo,'hash') and !stripos($ilo,'guid') and $ilo!='objectsid' and $ilo!='jpegphoto' and trim($i)!=''){
						$r0[]=$i;
					}
				}
				for($j=0; $j<count($r0); $j++){	// loop sulle righe
					$i=$r0[$j];
					if (!empty($en[0][$i][0])){
						$x='';
						if ($i=='xscadenzatempo' or $i=='x-scadenzatempo'){
							$d=substr($en[0][$i][0],0,8);
							$d=substr($d,6,2).'/'.substr($d,4,2).'/'.substr($d,0,4);
							$s.=get_alert('Fine rapporto : '.$d,'',true);
							$x='-';
						} 
						if ($i=='accountexpires'){
							$d = $en[0][$i][0];
							$winInterval = round($d / 10000000);
							$unixTimestamp = ($winInterval - 11644473600);
							$s.=get_alert('accountexpires (data) : '.date("d/m/Y", $unixTimestamp),'',true);
							$x='-';
						}
						if ($i=='dn'){
							$s.=get_alert('<h4>'.$i.' : <strong>'.$en[0][$i].'</strong></h4>','',true);
							$x='-';
						} 
						if ($x=='') {
							// if (count($en[0][$i])<=1){
							if (!is_array($en[0][$i])){
								$x=$en[0][$i];
								$s.=get_alert($i.' : <strong>'.$x.'</strong>','info',true);
							} else {
								$s.=get_alert('<h4 class="mt-2 mb-0">'.$i.'</h4>','primary',true);
								for ($z=0; $z<count($en[0][$i]); $z++){
									if (!empty($en[0][$i][$z])){
										$s.=get_alert(($z+1).' - <strong>'.$en[0][$i][$z].'</strong>','info',true);
									}
								}
							}
//							foreach ($en[0][$i] as $row){
//								// $x=$en[0][$i][0];
//								$s.=get_alert('- <strong>'.$row.'</strong>','info',true);
//							}
						}
					} else {$s.=get_alert($i,'warning',true);}
				}
			}
		} // else {$s.=print_r($en,true);}
	}			
	$s.='</div>';
	return $s;
}
function disp_var($v,$vn,$link){
	// $v = variabile da indagare, $vn = nome della variabile, $link = connessione se ldap
	// global $devel;
	// if (!$devel) {return;}
	$s="<hr><strong>$vn</strong><hr>";
//	echo "var_dump di $vn<br>";
//	var_dump($v);
//	echo "<br>print_r di $vn<br>";
//	print_r($v);
	$s.="<br>var_export di $vn<br>";
	$s.=print_r($v,true);
	$s.='<br>tipo di $vn = '.gettype($v);
	if (gettype($v)=='array'){
		$s.="<br>numero elementi di $vn = ".count($v);
		foreach ($v as $k => $d){
			$s.='<br>- '.$k.' = '.json_encode($d);
		}
		//  $k = array_keys($v);
		//  for ($i=0; $i<count($v); $i++) {
		//  	for ($j=0; $j<count($k); $j++) {
		//  		$s.='<br>- '.$k[$j].' = '.$v[$k[$j]][$i];
		//  	}
		//  }					
	}
	if (gettype($v)=='resource'){
		$s.="<br>tipo risorsa: ".get_resource_type($v);
		if (get_resource_type($v) == 'ldap result') {
			$errcode = $dn = $errmsg = $refs =  null;
			if (ldap_parse_result($link, $v, $errcode, $dn, $errmsg, $refs)) {
				$s.="<br>- errcode<br>".print_r($errcode,true);
				$s.="<br>- dn<br>".print_r($dn,true);
				$s.="<br>- errmsg<br>".print_r($errmsg,true);
				$s.="<br>- refs<br>".print_r($refs,true);
			}
		}
	}
	return $s;
}
function get_ldap_error($c='LDAP',$t=''){
//  function get_ldap_error($c){
//  	$s="<br><br>".ldap_error($c);
//  	ldap_get_option($c, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err);
//  	$s.="<br>ldap_get_option: $err";
//  	return $s;
//  }	
	global $ldap_conn, $ad_conn;
	if ($c=='LDAP' or $c==''){$c=$ldap_conn;}
	if ($c=='AD'){$c=$ad_conn;}
//	if ($c==''){$c=$ldap_conn;} // se non ho passato la connessione uso quella di ldap
	$s='';
	$en=ldap_errno($c);	// error number
	if ($en){	// se en != 0
		ldap_get_option($c, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err); // leggo dettagli errore
		$s=get_alert('<span class="text-danger">'.$t.ldap_error($c).' ('.$en.')<br>'.$err.'</span>','danger', true);
	}
	return $s;
}
function get_json_error(){
	$s='';
	switch (json_last_error()) {
		case JSON_ERROR_NONE:
			break;
		case JSON_ERROR_DEPTH:
			$s=' - Maximum stack depth exceeded';
			break;
		case JSON_ERROR_STATE_MISMATCH:
			$s=' - Underflow or the modes mismatch';
			break;
		case JSON_ERROR_CTRL_CHAR:
			$s=' - Unexpected control character found';
			break;
		case JSON_ERROR_SYNTAX:
			$s=' - Syntax error, malformed JSON';
			break;
		case JSON_ERROR_UTF8:
			$s=' - Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
		default:
			$s=' - Unknown error';
			break;
	}
	if ($s != '') {
		$s=get_alert('<span class="text-danger">'.$s.'</span>','danger');
	}
	return $s;
}
function safe_json_encode($value, $options = 0, $depth = 512, $utfErrorFlag = false){
	$encoded = json_encode($value, $options, $depth);
	switch (json_last_error()) {
		case JSON_ERROR_NONE:
			return $encoded;
		case JSON_ERROR_DEPTH:
			return 'Maximum stack depth exceeded'; // or trigger_error() or throw new Exception()
		case JSON_ERROR_STATE_MISMATCH:
			return 'Underflow or the modes mismatch'; // or trigger_error() or throw new Exception()
		case JSON_ERROR_CTRL_CHAR:
			return 'Unexpected control character found';
		case JSON_ERROR_SYNTAX:
			return 'Syntax error, malformed JSON'; // or trigger_error() or throw new Exception()
		case JSON_ERROR_UTF8:
			$clean = utf8ize($value);
			if ($utfErrorFlag) {
				return 'UTF8 encoding error'; // or trigger_error() or throw new Exception()
			}
			return safe_json_encode($clean, $options, $depth, true);
		default:
			return 'Unknown error'; // or trigger_error() or throw new Exception()
	}
}
function utf8ize($mixed) {
	if (is_array($mixed)) {
		foreach ($mixed as $key => $value) {
			$mixed[$key] = utf8ize($value);
		}
	} else if (is_string ($mixed)) {
		// return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
		return utf8_encode($mixed);
	}
	return $mixed;
}
function get_ad_data($u){
	global $ad_conn;
	$r=array();
	error_reporting(0);
	// sssapisa.it/ADprova/U.O. Servizi Prova
	$sr = ldap_search($ad_conn, 'ou=openldap,dc=sssapisa,dc=it', "(userprincipalname=$u@santannapisa.it)");
	if ($sr) {
		try {
			$en = ldap_get_entries($ad_conn, $sr);
			$nen = ldap_count_entries($ad_conn, $sr);
		} catch (Exception $e) {
			$r['errore_get_entries']='Message: ' .$e->getMessage();
		}
		if (!is_null($en)) {
			// $enk=array_keys($en);
			$enk=array('mailnickname','userprincipalname','xcodicefiscale','employeenumber','sn','givenname','mail','telephonenumber','mobile','departmentnumber','physicaldeliveryofficename','businesscategory','xscadenzatempo','accountexpires');
			for ($i=0; $i<count($enk); $i++){
				$p=$enk[$i];
				$r[$p]=$en[0][$p][0];
				if ($p=='xscadenzatempo'){
					$d=substr($en[0][$p][0],0,8);
					$d=substr($d,6,2).'/'.substr($d,4,2).'/'.substr($d,0,4);
					$r['xscadenzatempodata']=$d;
				}
				if ($p=='accountexpires'){
					$d = $en[0][$p][0];
					$winInterval = round($d / 10000000);
					$unixTimestamp = ($winInterval - 11644473600);
					$r['accountexpiresdata']=date("d/m/Y", $unixTimestamp);
				}
			}
		}					
	} else {
		$r['errore_cr']="Credenziali di ricerca (ACTIVE DIRECTORY) errate";
	}
	error_reporting(E_ALL);
	return $r;
}
function get_alert($x='',$stl='dark',$small=false){
	$s='';
	if ($small){
		$s.='<div class="alert-'.$stl.'">'.$x.'</div>';
	} else {
		// $s.='<div class="row py-1">';
			// $s.='<div class="col-sm">';
				// $s.='<div class="alert alert-'.$stl.'">'.$x.'</div>';
			// $s.='</div>';
		// $s.='</div>';		
		$s.='<div class="row py-1 alert alert-'.$stl.'">';
			$s.='<div class="col-sm">'.$x.'</div>';
		$s.='</div>';		
	}
	return $s;
}
function tolog_albo($messaggio){
	$logf='/var/www/html/ldap/IM_ALBO_'.date("Ym").'.log';
	if (!file_exists($logf)) {	// creo il file di log se non esiste
		$myfile = fopen($logf,"w") or die("Unable to open file!");
		fwrite($myfile,"");
		fclose($myfile);
		chmod($logf, 0777);
		tolog_albo("creato il file di log $logf");
	}
	$ip=getIpAddress();
	$uid='###### Albo';
	if (isset($_SESSION['IAM']['uid_login'])){$uid=$_SESSION['IAM']['uid_login'];}
	$napp=getcwd();
	file_put_contents($logf, $napp.' - ' . date("Ymd His").' - ip: '.$ip.' - uid: '.$uid.' - '.$messaggio."\r\n", FILE_APPEND | LOCK_EX);
}	
function computeDiff($from, $to){
    $diffValues = array();
    $diffMask = array();
    $dm = array();
    $n1 = count($from);
    $n2 = count($to);
    for ($j = -1; $j < $n2; $j++) $dm[-1][$j] = 0;
    for ($i = -1; $i < $n1; $i++) $dm[$i][-1] = 0;
    for ($i = 0; $i < $n1; $i++) {
        for ($j = 0; $j < $n2; $j++) {
            if ($from[$i] == $to[$j]) {
                $ad = $dm[$i - 1][$j - 1];
                $dm[$i][$j] = $ad + 1;
            } else {
                $a1 = $dm[$i - 1][$j];
                $a2 = $dm[$i][$j - 1];
                $dm[$i][$j] = max($a1, $a2);
            }
        }
    }
    $i = $n1 - 1;
    $j = $n2 - 1;
    while (($i > -1) or ($j > -1)) {
        if ($j > -1) {
            if ($dm[$i][$j - 1] == $dm[$i][$j]) {
                $diffValues[] = $to[$j];
                $diffMask[] = 1;
                $j--;  
                continue;              
            }
        }
        if ($i > -1) {
            if ($dm[$i - 1][$j] == $dm[$i][$j]) {
                $diffValues[] = $from[$i];
                $diffMask[] = -1;
                $i--;
                continue;              
            }
        }
        {
            $diffValues[] = $from[$i];
            $diffMask[] = 0;
            $i--;
            $j--;
        }
    }    
    $diffValues = array_reverse($diffValues);
    $diffMask = array_reverse($diffMask);
    return array('values' => $diffValues, 'mask' => $diffMask);
}
function diffline($line1, $line2){
	if ($line1==$line2){return $line1;}
    $diff = computeDiff(str_split($line1), str_split($line2));
    $diffval = $diff['values'];
    $diffmask = $diff['mask'];
    $n = count($diffval);
    $pmc = 0;
    $result = '';
    for ($i = 0; $i < $n; $i++) {
        $mc = $diffmask[$i];
        if ($mc != $pmc) {
            switch ($pmc) {
                // case -1: $result .= '</span>'; break;
                // case 1: $result .= '</span>'; break;
                case -1: $result .= ') '; break;
                case 1: $result .= '] '; break;
            }
            switch ($mc) {
                // case -1: $result .= '<span class=&quot;alert-danger&quot;>'; break;
                // case 1: $result .= '<span class=&quot;alert-success&quot;>'; break;
                case -1: $result .= ' ('; break;
                case 1: $result .= ' ['; break;
            }
        }
        $result .= $diffval[$i];
        $pmc = $mc;
    }
    switch ($pmc) {
        // case -1: $result .= '</span>'; break;
        // case 1: $result .= '</span>'; break;
        case -1: $result .= ') '; break;
        case 1: $result .= '] '; break;
    }
    return $result;
}
function mail_con_allegato($mittente, $destinatario, $oggetto, $messaggio, $allegato=''){

	//  // Recupero il valore dei campi del form
	//  $destinatario = 'info@mail-del-sito.com';
	//  $mittente = $_POST['mittente'];
	//  $oggetto = $_POST['oggetto'];
	//  $messaggio = $_POST['messaggio'];
	//  
	//  // Valorizzo le variabili relative all'allegato
	//  $allegato = $_FILES['allegato']['tmp_name'];
	//  $allegato_type = $_FILES['allegato']['type'];
	//  $allegato_name = $_FILES['allegato']['name'];

	// Creo altre due variabili ad uno interno
	$headers = "From: " . $mittente;
	$msg = "";

	// Verifico se il file è stato caricato correttamente via HTTP
	// In caso affermativo proseguo nel lavoro...
	// if (is_uploaded_file($allegato)) {
	if ($allegato != ''){	
		$allegato_name = basename($allegato).PHP_EOL;
	
		// Apro e leggo il file allegato
		$file = fopen($allegato,'rb');
		$data = fread($file, filesize($allegato));
		fclose($file);

		// Adatto il file al formato MIME base64 usando base64_encode
		$data = chunk_split(base64_encode($data));

		// Genero il "separatore"
		// Serve per dividere, appunto, le varie parti del messaggio.
		// Nel nostro caso separerà la parte testuale dall'allegato
		$semi_rand = md5(time());
		$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

		// Aggiungo le intestazioni necessarie per l'allegato
		$headers .= "\nMIME-Version: 1.0\n";
		$headers .= "Content-Type: multipart/mixed;\n";
		$headers .= " boundary=\"{$mime_boundary}\"";

		// Definisco il tipo di messaggio (MIME/multi-part)
		$msg .= "This is a multi-part message in MIME format.\n\n";

		// Metto il separatore
		$msg .= "--{$mime_boundary}\n";

		// Questa è la parte "testuale" del messaggio
		// $msg .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
		$msg .= "Content-type:text/html;charset=UTF-8\n";
		$msg .= "Content-Transfer-Encoding: 7bit\n\n";
		$msg .= $messaggio . "\n\n";

		// Metto il separatore
		$msg .= "--{$mime_boundary}\n";

		// Aggiungo l'allegato al messaggio
		$msg .= "Content-Disposition: attachment; filename=\"{$allegato_name}\"\n";
		$msg .= "Content-Transfer-Encoding: base64\n\n";
		$msg .= $data . "\n\n";

		// chiudo con il separatore
		$msg .= "--{$mime_boundary}--\n";
	} else {
		// se non è stato indicato alcun file come attachment
		// preparo un semplice messaggio testuale
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
		$headers .= 'From: '.$mittente."\r\n";
		$msg = $messaggio;
	}

	// Invio la mail mail($mail_sender,$subject,$message,$headers);
	if (mail($destinatario, $oggetto, $msg, $headers)) {
		tolog('Mail inviata con successo! '."\n");
		// echo "Mail inviata con successo!";
	} else {
		tolog('Errore invio mail! '."\n");
		// echo "Errore invio mail!";
	}
}
function convert_date($sdt,$ffrom='dd/mm/yyyy',$fto='yyyy-mm-dd'){
	// sdt = stringa data, ffrom = formato input, fto = formato output
	$sdt=substr($sdt,0,strlen($ffrom));
	$dd=substr($sdt,strpos($ffrom,'dd'),2);
	$mm=substr($sdt,strpos($ffrom,'mm'),2);
	$yyyy=substr($sdt,strpos($ffrom,'yyyy'),4);
	return str_replace('yyyy',$yyyy,str_replace('mm',$mm,str_replace('dd',$dd,$fto)));
}
function pwd_encryption($password){
	$password = '"' . $password . '"';
	if (function_exists('mb_convert_encoding')) {
		$password = mb_convert_encoding($password, 'UTF-16LE', 'UTF-8');
	} elseif (function_exists('iconv')) {
		$password = iconv('UTF-8', 'UTF-16LE', $password);
	} else {
		$len = strlen($password);
		$new = '';
		for ($i = 0; $i < $len; $i++) {
			$new .= $password[$i] . "\x00";
		}
		$password = $new;
	}
	return base64_encode($password);
}
function encodeToUtf8($string) {
	return mb_convert_encoding($string, "UTF-8", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
}
function getNextSerial($dn,$at){
	global $ldap_conn;
	$s='';
	$current_error_reporting = error_reporting();
	error_reporting(0);
	// cerco il dn con qualsiasi valore di at
	$search = ldap_search($ldap_conn,$dn,'('.$at.'=*)');
	$e=get_ldap_error();if ($e!=''){$s.=$e;}
	// leggo il contenuto
	$get = ldap_get_entries($ldap_conn, $search);
	$e=get_ldap_error();if ($e!=''){$s.=$e;}
	$j=json_encode($get);
	$n=$get[0][strtolower($at)][0];	// numero attuale
	$s.=get_alert('<strong>'.$at.'</strong><br><br>'.$j.'<br>'.$n,'success');
	
	// per effettuare le modifiche in qualsiasi dn mi connetto con manager
	$ldapBind = ldap_bind($ldap_conn,'cn=manager,o=sss,c=it','fugasse');
	$e=get_ldap_error();if ($e!=''){$s.=$e;}
	
	// cancella l'attributo contatore (se cancellabile)
	$results = @ldap_mod_del($ldap_conn,$dn,array($at => array()));	// per evitare l'errore usa @
	$e=get_ldap_error();	// if ($e!=''){$s.=$e;} // non registro errore
	$entry = array();
	$entry[$at][0] = $n+1;
	if ($e!=''){
		// la cancellazione ha dato errore provo con la modifica
		$results = @ldap_mod_replace($ldap_conn,$dn,$entry);
		$e=get_ldap_error();if ($e!=''){$s.=$e;}
	} else {
		// la cancellazione è andata a buon fine ... lo ricreo
		$results = @ldap_mod_add($ldap_conn,$dn,$entry);
		$e=get_ldap_error();if ($e!=''){$s.=$e;}
	}
	error_reporting($current_error_reporting);
	return array('n' => $n, 'msg' => $s);
}
function analyse_file($file, $capture_limit_in_kb = 10) {
	/* 
	// Example Usage:
	// $Array = analyse_file('/www/files/file.csv', 10);

	// example usable parts
	// $Array['delimiter']['value'] => ,
	// $Array['line_ending']['value'] => \r\n

	Array (
			[peak_mem] => Array (	[start] => 786432 [end] => 786432 )
			[line_ending] => Array (
							[results] => Array ([nr] => 0	[r] => 4	[n] => 4	[rn] => 4	)
							[count] => 4
							[key] => rn
							[value] =>
					)
			[lines] => Array ([count] => 4	[length] => 94 )
			[delimiter] => Array (
							[results] => Array ([colon] => 0	[semicolon] => 0	[pipe] => 0	[tab] => 1	[comma] => 17 )
							[count] => 17
							[key] => comma
							[value] => ,
					)
			[read_kb] => 10
	)
*/

	// capture starting memory usage
	$output['peak_mem']['start']    = memory_get_peak_usage(true);

	// log the limit how much of the file was sampled (in Kb)
	$output['read_kb']                 = $capture_limit_in_kb;
 
	// read in file
	$fh = fopen($file, 'r');
			$contents = fread($fh, ($capture_limit_in_kb * 1024)); // in KB
	fclose($fh);
 
	// specify allowed field delimiters
	$delimiters = array(
			'comma'     => ',',
			'semicolon' => ';',
			'tab'         => "\t",
			'pipe'         => '|',
			'colon'     => ':'
	);
 
	// specify allowed line endings
	$line_endings = array(
			'rn'         => "\r\n",
			'n'         => "\n",
			'r'         => "\r",
			'nr'         => "\n\r"
	);
 
	// loop and count each line ending instance
	foreach ($line_endings as $key => $value) {
			$line_result[$key] = substr_count($contents, $value);
	}
 
	// sort by largest array value
	asort($line_result);
 
	// log to output array
	$output['line_ending']['results']     = $line_result;
	$output['line_ending']['count']     = end($line_result);
	$output['line_ending']['key']         = key($line_result);
	$output['line_ending']['value']     = $line_endings[$output['line_ending']['key']];
	$lines = explode($output['line_ending']['value'], $contents);
 
	// remove last line of array, as this maybe incomplete?
	array_pop($lines);
 
	// create a string from the legal lines
	$complete_lines = implode(' ', $lines);
 
	// log statistics to output array
	$output['lines']['count']     = count($lines);
	$output['lines']['length']     = strlen($complete_lines);
 
	// loop and count each delimiter instance
	foreach ($delimiters as $delimiter_key => $delimiter) {
			$delimiter_result[$delimiter_key] = substr_count($complete_lines, $delimiter);
	}
 
	// sort by largest array value
	asort($delimiter_result);
 
	// log statistics to output array with largest counts as the value
	$output['delimiter']['results']     = $delimiter_result;
	$output['delimiter']['count']         = end($delimiter_result);
	$output['delimiter']['key']         = key($delimiter_result);
	$output['delimiter']['value']         = $delimiters[$output['delimiter']['key']];
 
	// capture ending memory usage
	$output['peak_mem']['end'] = memory_get_peak_usage(true);
	return $output;
}
function get_upload_dialog($t='File upload',$m='',$l='carica file:',$accept="",$sact="",$tab="",$getf="",$gett=""){
	// $accept=".csv,.txt"
	$s=$m;
	// form di richiesta in modal
	$s.='<div class="row">';
		$s.='<div class="col-sm-2 text-right">';
			$s.='<label for="fileToUpload">'.$l.'</label>';
		$s.='</div>';
		$s.='<div class="col-sm-8">';
			$s.='<input type="file" name="fileToUpload" id="fileToUpload" class="form-control"';
			if (!empty($accept)){$s.=' accept="'.$accept.'"';}
			$s.=' />';
		$s.='</div>';
		$s.='<div class="col-sm-2">';
			// $s.='<a act="up" class="btn btn-sm btn-success text-nowrap text-white btn-block">Carica</a>';
		$s.='</div>';
	$s.='</div>';

	$stl='';
	$btn='';
	if (!empty($tab)){$tab='tab="'.$tab.'"';}
	if (!empty($sact)){$sact='sact="'.$sact.'"';}
	if (!empty($getf)){$getf='getf="'.$getf.'"';}
	if (!empty($gett)){$gett='gett="'.$gett.'"';}
	$btn='<button id="modal-ok" class="btn btn-success btn-block" data-dismiss="modal" act="up" dom="mm" '.$tab.' '.$sact.' '.$getf.' '.$gett.'>Carica</button><button id="modal-ko" class="btn btn-dark btn-block" data-dismiss="modal">Annulla</button>';
	$r=array('tit' => $t, 'msg' => $s, 'stl' => $stl, 'btn' => $btn);
	$s=json_encode($r);			
	return $s;
}
function upload_file($path,$maxSize=5000000,$aea=array('csv','txt')){
	global $sviluppo_albo;
	if (!isset($_FILES)){return;}
	$m='$_FILES: '.print_r($_FILES,true);
	if (isset($_REQUEST)){$m.="\n".'$_REQUEST: '.print_r($_REQUEST,true);}
	tolog($m);
	try {
		$a=array_keys($_FILES);
		$aun=$a[0];
		$nf = $_FILES[$aun]['name']; 			// nome file
		$df = $_FILES[$aun]['size'];			// size
		$tf = $_FILES[$aun]['tmp_name']; 		// nome file temporaneo
		$e = $_FILES[$aun]['error'];			// errore
		$t = $_FILES[$aun]['type'];				// tipo
		$pnf=$path.$nf;							// path nome file target
	} catch(Throwable $e) {
		$e1=error_get_last();
		tolog(print_r($e1,true));
		$r=array('tit' => 'errore', 'msg' => $e1.'<br'.$e1, 'stl' => '', 'btn' => '', 'dom' => 'mm');
		echo base64_encode(json_encode($r));	
		return;
	}
	$m='';
	if ($df > $maxSize and $maxSize > 0) {
		$smb=round($maxSize/1000000);
		$m.='<br><div class="alert alert-danger">Le dimensioni del file <strong>'.$nf.'</strong> eccedono il massimo di '.$smb.'Mb.</div>';
		// unlink($pnf);
	}
	$ext=strtolower(pathinfo($nf,PATHINFO_EXTENSION));
	// $aea=array('csv','txt'); // array estensioni ammesse
	if (!in_array($ext, $aea)){
		$m.='<br><div class="alert alert-danger">Puoi caricare solo file di tipo <strong>'.implode(', ',$aea).'</strong></div>';
	}
	$b="";
	$tit="<h2>Carica file</h2>";
	if ($m==''){
		if ($e == UPLOAD_ERR_OK) {
//				$xml = simplexml_load_file($_FILES['file']['tmp_name']);
//				$m.='<br>'.$xml;
			try {
				// https://www.php.net/manual/en/function.move-uploaded-file.php
				// Warning If the destination file already exists, it will be overwritten.
				move_uploaded_file($_FILES[$aun]['tmp_name'], $pnf);
				$_SESSION['IAM']['last_file_uploaded']=$pnf;
			} catch(Throwable $e) {
				tolog(print_r(error_get_last(),true));
			}
			$m.=get_alert('il file: <strong>'.$nf.'</strong> di '.$df.' byte &egrave; stato correttamente caricato.','success');
		} else {
			$m1='';
			switch ($e){
				case 1: $m1='File di dimensioni eccedenti '.ini_get('upload_max_filesize'); break;
				case 2: $m1='File di dimensioni eccedenti '.ini_get('upload_max_filesize'); break; // definite nella form
				case 3: $m1='File caricato solo parzialmente'; break;
				case 4: $m1='Nessun file caricato'; break;
				// case 5: $m1=''; break;
				case 6: $m1='Accesso alla dir temporanea non permesso'; break;
				case 7: $m1='Errore scrittura del file sul server'; break;
				case 8: $m1='Estensione del file non permessa'; break;
			}
			$m.=get_alert('il file: <strong>'.$nf.'</strong> NON &egrave; stato correttamente caricato.'.'<br />'.$m1,'danger');				  
		}
		$stl='bg-success text-center text-white';
	} else {
		$m.=get_alert('il file: <strong>'.$nf.'</strong> di '.$df.' byte <strong>NON</strong> &egrave; stato <strong>caricato.</strong>','danger');
		$stl='bg-danger text-center text-white';
	}
	$r='';
	if (!empty($_REQUEST['sact'])){$_SESSION['IAM']['att_after_upload']=$_REQUEST['sact'];}
	if (!empty($_SESSION['IAM']['att_after_upload'])){
		tolog('call_user_func: '.'aau_'.$_SESSION['IAM']['att_after_upload']);
		if (function_exists('aau_'.$_SESSION['IAM']['att_after_upload'])) {
			call_user_func('aau_'.$_SESSION['IAM']['att_after_upload']);
/*
			$s='<script type="text/javascript" id="js_au">
				$("body").append(\'<a id="b_aau" href="#" style="display: none" act="aau_'.$_SESSION['IAM']['att_after_upload'].'" dom="mm">ATT AFTER UPLOAD</a>\');
				listener();
				$("#b_aau")[0].click();
				$("#b_aau").remove();
				// $("#notification").fadeOut(300, function() { $(this).remove(); });
				$("#js_au").remove();
			</script>';
			unset($_SESSION['IAM']['att_after_upload']);
			echo base64_encode($s);
*/			
		}
	}
	//  if (is_array($r) or is_object($r)){
	//  	echo base64_encode(json_encode($r));
	//  } else {
	//  	// if ($sviluppo_albo){
	//  		tolog('ritorna da call_user_func: '.$r);
	//  	// }
	//  	$m.=$r;
	//  	$r=array('tit' => $tit, 'msg' => $m, 'stl' => $stl, 'btn' => $b, 'dom' => 'mm');
	//  	echo base64_encode(json_encode($r));
	//  }
	//  tolog("\nUpload file:\n".print_r($_FILES,true));
}
function oraexec($o){
	
	// --- $o = array o string (minimo contenuto = $o['sql'] o $sql
	if (empty($o)){return;}
	// set defaults
	$fret=false;
	$tipo='o';
	$tolog=false;
	$sql='';
	if (is_array($o)){
		// per insert, update o delete richiede di sapere quante righe ha coinvolto
		// per select rtorna sempre l'array (return nc nr)
		if (!empty($o['fret'])){$fret=$o['fret'];}		
		if (!empty($o['tipo'])){$tipo=$o['tipo'];}		// o stile oracle, altro = stile mysql
		if (!empty($o['conn'])){$conn=$o['conn'];}		// connessione da usare
		if (!empty($o['sql'])){$sql=$o['sql'];}			// sql
		if (!empty($o['tolog'])){$tolog=$o['tolog'];}	// da scrivere su log (true/false)
	} else {
		// non è un array controllo se è una stringa sql di lettura, inserimento, modifica o cancellazione
		if (!is_string($o)){return;}
		$a=explode(" ",strtolower($o));
		// per gli altri tipi di esecuzione oracle usare l'oggetto
		if (!in_array($a[0],array('select','insert','update','delete'))){return;}
		$sql=$o;
	}
	if ($sql==''){return;}
	if (empty($conn)){	
		// non è stata specificata la connessione al db oracle
		if (!empty($GLOBALS['conn_new'])){
			// se $conn_new è stato definito lo uso
			$conn=$GLOBALS['conn_new'];
		} else {
			// default interscambio
			$u='C##SSS_IMPORT'; $p='ugovimport'; $c='(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.140)(PORT=1521))(CONNECT_DATA=(SID=XE)))'; 
			$conn = oci_pconnect($u,$p,$c,'AL32UTF8');	// in utf8
			if (empty($conn)) {
				return '<h2>problemi di connessione al db di interscambio</h2>';;
			}
			$sql = "ALTER SESSION SET NLS_DATE_FORMAT = 'DD/MM/YYYY HH24:MI:SS'";
			$p = oci_parse($conn, $sql);
			$e = oci_execute($p);
		}
	}
	if ($tolog){tolog($sql);}
	$a=explode(" ",strtolower($sql));
	$ret=false; if ($a[0]=='select'){$ret=true;}
	if ($fret === true){$ret=true;}
	$p = oci_parse($conn, $sql);
	if ($p){
		$x = oci_execute($p);
	}
	$e = oci_error($conn); 
	if (!empty($e)){return '<br />'.$sql.'<br />'.json_encode($e);}

	if ($ret){
		$result=array();
		if ($a[0]=='select'){
			if ($tipo=='o'){$result['nr'] = oci_fetch_all($p, $r);}
			else {$result['nr'] = oci_fetch_all($p, $r, null, null, OCI_FETCHSTATEMENT_BY_ROW);}
			$result['nc']=oci_num_fields($p);
			$result['result']=$r;
		} else {
			$result['nr'] = oci_num_rows($p);
		}
		return $result;
	}
	function tolog($messaggio){
		$app=get_napp();
		$logf=checklog($app);
		$ip=getIpAddress();
		$uid='######';
		if (!empty($_SESSION['uid_login'])){$uid=$_SESSION['uid_login'];}
		if (!empty($_SESSION[$app]['uid_login'])){$uid=$_SESSION[$app]['uid_login'];}
		$napp=getcwd() . ' (' . __FILE__ . ')';
		file_put_contents($logf, $napp.' - ' . date("Y/m/d H:i:s").' - ip: '.$ip.' - uid: '.$uid.' - '.$messaggio."\r\n", FILE_APPEND | LOCK_EX);
		function get_napp(){
			if (empty('IAM')){
				$nf=strtoupper(__FILE__);
				$anf=explode('/',$nf);
				$eanf=end($anf);
				$anf=explode('.',$eanf);
				$r=$anf[0];
			} else {
				$r='IAM';
			}
			return $r;
		}
		function checklog($app){
			if ($app=='RAC'){$logf='log/'.$app.'_'.date("Ym").'.log';}
			else {$logf='/var/www/log/'.$app.'_'.date("Ym").'.log';}
			if (!file_exists($logf)) {	// creo il file di log se non esiste
				$myfile = fopen($logf,"w") or die("Unable to open file!");
				fwrite($myfile,"");
				fclose($myfile);
				chmod($logf, 0777);
				// tolog("creato il file di log $logf");
			};
			return $logf;
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
			return $ip;
		}	
	}
}
function write_log($f, $m){
	if (empty($m)){return;}
	if (empty($f)){$f='_tmp.log';}
	if (empty($_SESSION['IAM']['uid_login'])){$uid='######';} else {$uid=$_SESSION['IAM']['uid_login'];}
	$ora=round(microtime(true));			// tempo attuale in secondi
	if (file_exists($f)){
		file_put_contents($f, date("Y-m-d H:i:s",$ora)." - ".$uid." - ".$m."\r\n", FILE_APPEND | LOCK_EX);
	} else {
		file_put_contents($f, date("Y-m-d H:i:s",$ora)." - ".$uid." - ".$m."\r\n", LOCK_EX);
	}
}
?>
