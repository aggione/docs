<?php
// https://iam.local.santannapisa.it/__check_db.php
// https://iam.local.santannapisa.it/__check_db.php?html=1
// https://iam.local.santannapisa.it/__check_db.php?html=1&toc=AD|UGOV
// https://iam.local.santannapisa.it/__check_db.php?toc=IAM
// https://iam.local.santannapisa.it/__check_db.php?toc=ORAXEWIN10

/*

1			E_ERROR							Fatal run-time errors. Errors that cannot be recovered from. Execution of the script is halted
2			E_WARNING						Run-time warnings (non-fatal errors). Execution of the script is not halted
4			E_PARSE							Compile-time parse errors. Parse errors should only be generated by the parser
8			E_NOTICE						Run-time notices. The script found something that might be an error, but could also happen when running a script normally
16		E_CORE_ERROR				Fatal errors at PHP startup. This is like E_ERROR, except it is generated by the core of PHP
32		E_CORE_WARNING			Non-fatal errors at PHP startup. This is like E_WARNING, except it is generated by the core of PHP
64		E_COMPILE_ERROR			Fatal compile-time errors. This is like E_ERROR, except it is generated by the Zend Scripting Engine
128		E_COMPILE_WARNING		Non-fatal compile-time errors. This is like E_WARNING, except it is generated by the Zend Scripting Engine
256		E_USER_ERROR				Fatal user-generated error. This is like E_ERROR, except it is generated in PHP code by using the PHP function trigger_error()
512		E_USER_WARNING			Non-fatal user-generated warning. This is like E_WARNING, except it is generated in PHP code by using the PHP function trigger_error()
1024	E_USER_NOTICE				User-generated notice. This is like E_NOTICE, except it is generated in PHP code by using the PHP function trigger_error()
2048	E_STRICT						Enable to have PHP suggest changes to your code which will ensure the best interoperability and forward compatibility of your code (Since PHP 5 but not included in E_ALL until PHP 5.4)
4096	E_RECOVERABLE_ERROR	Catchable fatal error. Indicates that a probably dangerous error occurred, but did not leave the Engine in an unstable state. If the error is not caught by a user defined handle, the application aborts as it was an E_ERROR (Since PHP 5.2)
8192	E_DEPRECATED				Run-time notices. Enable this to receive warnings about code that will not work in future versions (Since PHP 5.3)
16384	E_USER_DEPRECATED		User-generated warning message. This is like E_DEPRECATED, except it is generated in PHP code by using the PHP function trigger_error() (Since PHP 5.3)
32767	E_ALL								Enable all PHP errors and warnings (except E_STRICT in versions < 5.4)

*/


$er=error_reporting();
// echo $er;	// 32767 = E_ALL
set_error_handler('alboError');

$j=file_get_contents('./__check_db.json');
$j=json_decode($j,true);

// riporto $argv in $_REQUEST
if (!empty($argv)){
	if (is_array($argv)){
		for ($i = 0; $i < count($argv); $i++) {
			if ($i > 0){ // $argv[0] è il nome dei questo script
				if (strpos($argv[$i],'=') !== false){
					$a=explode('=',$argv[$i]);
					$a0=trim($a[0]);
					$a1=trim($a[1]);
					$_REQUEST[$a0]=$a1;
				}
			}
		}
	}
}

// se tra gli argomenti c'è db o app o toc restringo la ricerca al solo elemento specificato (non prevedo per ora scelte multiple ... magari separate da |
$kk=array('toc','db','app');
$allow=array();
foreach ($kk as $ki){
	if (!empty($_REQUEST[$ki])){
		if (strpos($_REQUEST[$ki],'|') === false){
			$allow[]=$_REQUEST[$ki];
		} else {
			$allow=explode('|',$_REQUEST[$ki]);
		}
	}
}
if (empty($allow)){
	$allow=array_merge(
		array_keys($j['db']['adldap'])
		,array_keys($j['db']['mssql'])
		,array_keys($j['db']['mysql'])
		,array_keys($j['db']['oracle'])
		,array_keys($j['db']['postgres'])
		,array_keys($j['app']['dotnetu'])
		,array_keys($j['app']['cineca'])
		,array_keys($j['app']['iam'])
	);
}
$x=0;
if (!empty($_REQUEST['html'])){
	session_start();
	echo get_html();
} else {
	$s=get_html();
	// $x = numero errori riscontrati
	// if ($x==0){$x=1;} else {$x=0;} // per 1 = corretto e 0 = con errori
	echo $x;	
}

function get_html(){
	$s='<!DOCTYPE html>
	<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<meta name="description" content="Check">
			<meta name="keywords" content="Check">
			<meta name="author" content="Alberto Bongiorni">

			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
			
			<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
			<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.slim.min.js"></script>
			<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
			<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>			
			
			<title>Check</title>

			<script>
				$(document).ready(function(){
					$(\'[data-toggle="popover"]\').popover();   
				});
			</script>		

		</head>
		<body>'.get_home().'</body>
	</html>
	';
	return $s;
}
function get_home(){
	global $x, $j, $allow;
	$s='';

	$start = new DateTime( 'NOW' );

	$s.='<div class="container">';

		$s.=get_alert('<h2>Check servizi Scuola Sant\'Anna - IAM</h2>','warning text-center');
		
		// check database
		$db=$j['db'];
		$o=array();
		foreach ($db as $t => $dbv){
			$s.=get_alert($t);	// tipo
			foreach ($dbv as $kd => $d){
				$e='';
 				$starti = new DateTime( 'NOW' );
				if ($d['attivo']){$c='success';} else {$c='danger';}
				if ($d['attivo'] and in_array($kd,$allow)){
					$s.='<div class="row">';
						$s.='<div class="col-sm-1"></div>';
						$s.='<div class="col-sm">';
							$pod=''; foreach ($d as $dk => $dv){$pod.='<strong>'.$dk.'</strong>: '.$dv."<br />";}
							$po='title="'.$d['d'].'" data-toggle="popover" data-trigger="hover" data-html="true" data-content="'.$pod.'"';
							$s.=get_alert('<strong '.$po.'>'.$d['d'].' </strong>',$c);
						$s.='</div>';		
						// connessione
						$s.='<div class="col-sm">';
						$d['error']='';
						switch($t){
							case 'oracle':
								if (empty($d['sql'])){
									$sql = "select 'a' as a from dual";
								} else {
									$sql=$d['sql'];
								}
								try {
									$conn = @oci_pconnect($d['u'],$d['p'],$d['tnsx'],'AL32UTF8');	// in utf8
									$e = oci_error();
									if ($e != '') {
										$x++;
										$s.=get_alert('<strong>ERRORE CONNESSIONE</strong>: '.json_encode($e),'danger');
										$d['error']=json_encode($e);
									} else {
										$s.=get_alert('CONNESSO','success');
									}
								} catch (Exception $e) {
									$x++;
									$s.=get_alert('<strong>ERRORE CONNESSIONE</strong>: '.json_encode($e),'danger');
									$d['error']=json_encode($e);
								}
								break;
							case 'postgres':
								if (empty($d['sql'])){
									$sql = "select n from generate_series(1,5) s(n)";
								} else {
									$sql=$d['sql'];
								}
								try {
									$u=$d['u'];
									$p=$d['p'];
									try {
										//  pg_connect("host=localhost dbname=edb user=enterprisedb password=postgres");
										$conn=@pg_connect($d['tnsx']." user=$u"." password=$p");
										$s.=get_alert('CONNESSO','success');
									} Catch (Exception $e) {
										$x++;
										$s.=get_alert('<strong>ERRORE CONNESSIONE</strong>: '.json_encode($e),'danger');
										$d['error']=json_encode($e);
									}								
								} catch (Exception $e) {
									$x++;
									$s.=get_alert('<strong>ERRORE CONNESSIONE</strong>: '.json_encode($e),'danger');
									$d['error']=json_encode($e);
								}
								break;
							case 'mysql':
								if (empty($d['sql'])){
									$sql = "SELECT 1 as n FROM DUAL";
								} else {
									$sql=$d['sql'];
								}
								try {
									$conn = @mysqli_connect($d['tns'], $d['u'], $d['p'], $d['db']);
									if (mysqli_connect_errno()) {
										$e = mysqli_connect_error();
										$x++;
										$s.=get_alert('<strong>ERRORE CONNESSIONE</strong>: '.json_encode($e),'danger');
										$d['error']=json_encode($e);
									} else {
										$s.=get_alert('CONNESSO','success');
									}
								} catch (Exception $e) {
									$x++;
									$s.=get_alert('<strong>ERRORE CONNESSIONE</strong>: '.json_encode($e),'danger');
									$d['error']=json_encode($e);
								}
								break;
							case 'mssql':
								if (empty($d['sql'])){
									$sql = "SELECT 1 as n";
								} else {
									$sql=$d['sql'];
								}
								try {
									
									// $c = new PDO("sqlsrv:Server=localhost;Database=testdb", "UserName", "Password");
									// $c = new PDO("sqlsrv:Server=localhost,1521;Database=testdb", "UserName", "Password");
									// $c = new PDO("sqlsrv:Server=12345abcde.database.windows.net;Database=testdb", "UserName@12345abcde", "Password");

									// $conn = new PDO("sqlsrv:Server=".$d['host'].";Database=".$d['db'], $d['u'], $d['p']);
									// $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
									
									$conn_inf = array( "Database"=>$d['db'], "UID"=>$d['u'], "PWD"=>$d['p'] );
									$conn = @sqlsrv_connect( $d['host'], $conn_inf );
									if( $conn ) {
										$s.=get_alert('CONNESSO','success');
									} else {
										$e = sqlsrv_errors();
										$x++;
										$s.=get_alert('<strong>ERRORE CONNESSIONE</strong>: '.json_encode($e),'danger');
										$d['error']=json_encode($e);
									}
								} catch (Exception $e) {
									$x++;
									$s.=get_alert('<strong>ERRORE CONNESSIONE</strong>: '.json_encode($e),'danger');
									$d['error']=json_encode($e);
								}
								break;
							case 'adldap':
								try {
									$conn = @ldap_connect($d['host'],intval($d['port']));
									$e = ldap_error($conn);
									if (empty($e)){
										$s.=get_alert('CONNESSO','success');
									} else {
										if ($e=='Success'){
											$s.=get_alert('CONNESSO','success');
											$e='';
										} else {
											$x++;
											$s.=get_alert('<strong>ERRORE CONNESSIONE</strong>: '.json_encode($e),'danger');
											$d['error']=json_encode($e);
										}
									}
								} catch (Exception $e) {
									$x++;
									$s.=get_alert('<strong>ERRORE CONNESSIONE</strong>: '.json_encode($e),'danger');
									$d['error']=json_encode($e);
								}
								break;
							default:
								break;
						}
						$s.='</div>';		
						if (empty($e)){
							// esecuzione sql di lettura
							$s.='<div class="col-sm">';
								switch($t){
									case 'oracle':
										try {
											$p = @oci_parse($conn, $sql);
											$e = oci_error($conn);
											if ($e != '') {
												$x++;
												$s.=get_alert('ERRORE LETTURA: '.json_encode($e),'danger');
												$d['error']=json_encode($e);
											} else {
												$r = @oci_execute($p);
												$e = oci_error($conn);
												if (empty($e)){
													$s.=get_alert('LETTURA CORRETTA','success');
												} else {
													$x++;
													$s.=get_alert('ERRORE LETTURA: '.json_encode($e),'danger');
													$d['error']=json_encode($e);
												}
											}
										} catch (Exception $e) {
											$x++;
											$s.=get_alert('<strong>ERRORE LETTURA: </strong>: '.json_encode($e),'danger');
											$d['error']=json_encode($e);
										}
										break;
									case 'postgres':
										try {
											$p = @pg_query($conn, utf8_encode($sql));
											// $p = @pg_query($conn, $sql);
											$e = pg_last_error($conn);
											if ($e != '') {
												$x++;
												$s.=get_alert('ERRORE LETTURA: '.json_encode($e),'danger');
												$d['error']=json_encode($e);
											} else {
												$r = @pg_fetch_all($p);
												$e = pg_last_error($conn);
												if (empty($e)){
													$s.=get_alert('LETTURA CORRETTA','success');
												} else {
													$x++;
													$s.=get_alert('ERRORE LETTURA: '.json_encode($e),'danger');
													$d['error']=json_encode($e);
												}
											}
										} catch (Exception $e) {
											$x++;
											$s.=get_alert('<strong>ERRORE LETTURA: </strong>: '.json_encode($e),'danger');
											$d['error']=json_encode($e);
										}
										break;
									case 'mysql':
										try {
											$r = @mysqli_query($conn,$sql);
											// $nr=mysqli_num_rows($result);
											// $nc=mysqli_num_fields($result);
											$e = mysqli_error($conn);
											if (empty($e)){
												$s.=get_alert('LETTURA CORRETTA','success');
											} else {
												$x++;
												$s.=get_alert('ERRORE: '.json_encode($e),'danger');
												$d['error']=json_encode($e);
											}
										} catch (Exception $e) {
											$x++;
											$s.=get_alert('<strong>ERRORE LETTURA: </strong>: '.json_encode($e),'danger');
											$d['error']=json_encode($e);
										}
										break;
									case 'mssql':
										try {
											// $prep1 = $conn->prepare($sql);
											// $r = $conn->exec($prep1->queryString);									
											
											$r = @sqlsrv_query($conn,$sql);
											$e = sqlsrv_errors();
											if (empty($e)){
												$s.=get_alert('LETTURA CORRETTA','success');
											} else {
												$x++;
												$s.=get_alert('ERRORE: '.json_encode($e),'danger');
												$d['error']=json_encode($e);
											}
										} catch (Exception $e) {
											$x++;
											$s.=get_alert('<strong>ERRORE LETTURA: </strong>: '.json_encode($e),'danger');
											$d['error']=json_encode($e);
										}
										break;
									case 'adldap':
										try {
											ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
											ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
											$e = ldap_error($conn);
											if (empty($e)){
												$r = @ldap_bind($conn,$d['u'],$d['p']);
												if (!empty($r)){
													$s.=get_alert('LETTURA CORRETTA','success');
												} else {
													$e = ldap_error($conn);
													if ($e=='Success'){
														$s.=get_alert('LETTURA CORRETTA','success');
														$e='';
													} else {
														$x++;
														$s.=get_alert('ERRORE: '.json_encode($e),'danger');
														$d['error']=json_encode($e);
													}
												}
											} else {
												if ($e=='Success'){
													$s.=get_alert('LETTURA CORRETTA','success');
													$e='';
												} else {
													$x++;
													$s.=get_alert('ERRORE: '.json_encode($e),'danger');
													$d['error']=json_encode($e);
												}
											}
										} catch (Exception $e) {
											$x++;
											$s.=get_alert('<strong>ERRORE LETTURA: </strong>: '.json_encode($e),'danger');
											$d['error']=json_encode($e);
										}
										break;
									default:
										break;
								}
							$s.='</div>';	
							// se era connesso, chiudo la connessione
							switch($t){
								case 'oracle':
									@oci_close($conn);
									break;
								case 'postgres':
									@pg_close($conn);
									break;
								case 'mysql':
									@mysqli_close($conn);
									break;
								case 'mssql':
									// $conn=null;
									@sqlsrv_close($conn);
									break;
								case 'adldap':
									@ldap_close($conn);
									break;
								default:
									break;
							}
						}
						$urll='./files_check/'.$kd.'.txt';
						file_put_contents($urll,json_encode($d,JSON_PRETTY_PRINT));

						$endi = new DateTime( 'NOW' );
						$tei = $endi->getTimestamp() - $starti->getTimestamp();	// tempo esecuzione item
						$s.='<div class="col-sm">'.get_alert($tei.' secondi','info').'</div>';
					$s.='</div>';		
				}
			}
		}

		// check apps
		$app=$j['app'];
		$dotnetu=$app['dotnetu'];
		foreach ($app as $appsk => $appsi){
			$s.=get_alert($appsk);	// tipo
			foreach ($appsi as $ak => $ai){
				if (!empty($ai['attivo']) and !empty($ai['url']) and in_array($ak,$allow)){
					$e='';
					$starti = new DateTime( 'NOW' );
					if (isset($_SERVER['PHP_AUTH_USER'])){unset($_SERVER['PHP_AUTH_USER']);}
					if (!empty($ai['u'])){$_SERVER['PHP_AUTH_USER']=$ai['u'];}
					$s.='<div class="row">';
						$s.='<div class="col-sm-1"></div>';

						$s.='<div class="col-sm">';
							$pod=''; foreach ($ai as $aik => $aiv){$pod.='<strong>'.$aik.'</strong>: '.$aiv."<br />";}
							$po='title="'.$ai['d'].'" data-toggle="popover" data-trigger="hover" data-html="true" data-content="'.$pod.'"';
							$s.=get_alert('<strong '.$po.'>'.$ai['d'].' </strong>','success');
						$s.='</div>';

						$s.='<div class="col-sm">';
						
							$ff='';
							// @@@@@@ Risolvere .htaccess per evitare shibboleth
							
							$url=$ai['url'];

							if (false){
								$ch = curl_init(); 															// Initialize a CURL session.
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		// Return Page contents.
								curl_setopt($ch, CURLOPT_URL, $url);						// grab URL and pass it to the variable.
								$ff = curl_exec($ch);						
							}
							
							$scc=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,));
							
							if (false){
								if (empty($ff)){
									$ff = file_get_contents($url, false, stream_context_create($scc));
								}
							}

							if (true){
								$ff = get_headers($url, true, stream_context_create($scc));
							}

							if (!empty($ff)){

								$fff='';
								for($i=0; $i<=9; $i++){
									if (!empty($ff[$i])){$fff=$ff[$i];}
								}

								$urll='./files_check/'.$ak.'_completo.txt';
								file_put_contents($urll,json_encode($ff,JSON_PRETTY_PRINT));
								
								if (substr($fff,9,3) != '200'){
									$x++;
									$e=$fff;
									// $e=substr($fff,9,3);
									$s.=get_alert($fff,'danger');
								}
								
								if (empty($e)){
									// $urll=__DIR__.'/files_check/'.$ak.'.txt';
									$urll='./files_check/'.$ak.'.txt';
									if (file_exists($urll)){
										$ll = file_get_contents($urll); // legge il sorgente e non il risultato della pagina tramite http
										if ($fff == $ll){
											$s.=get_alert($fff,'success');
											$e='';
										} else {
											$x++;
											$e='html cambiato';
											$s.=get_alert('ERRORE: '.json_encode($e),'danger');
											$urll='./files_check/'.$ak.'_cambiato.txt';
											file_put_contents($urll,$fff);
										}
									} else {
										// il file di confronto non esiste ... lo creo
										file_put_contents($urll,$fff);
										$s.=get_alert('LETTURA CORRETTA<br>creato file: '.$urll.'<br>'.strlen($fff).' bites','success');
										$e='';
									}
								}
							} else {
								$x++;
								$e='impossibile leggere l\'applicazione';
								$s.=get_alert('ERRORE: '.json_encode($e),'danger');
							}
						$s.='</div>';
						$endi = new DateTime( 'NOW' );
						$tei = $endi->getTimestamp() - $starti->getTimestamp();	// 			
						$s.='<div class="col-sm">'.get_alert($tei.' secondi','info').'</div>';
					$s.='</div>';		
				}
			}
		}

		$end = new DateTime( 'NOW' );
		$te = $end->getTimestamp() - $start->getTimestamp();	// tempo esecuzione

		$s.='<div class="row">';
			$s.='<div class="col-sm">'.get_alert('Tempo esecuzione totale: <strong>'.$te.'</strong> secondi','warning').'</div>';
			$s.='<div class="col-sm">'.get_alert('Numero totale errori rilevati: <strong>'.$x.'</strong>','warning').'</div>';
		$s.='</div>';		

	$s.='</div>';		

	// $s.=get_alert(json_encode($allow));	// @@@@@

	$s.='';
	return $s;
}
//  function get_opt(){
//  	$opts = array(
//  		'http'=>array(
//  			'header' => "Content-type: application/x-www-form-urlencoded\r\n",
//  			'method' => 'POST',
//  			'content' => http_build_query($data),
//  		),
//  		"ssl"=>array(
//  				'method'=>"POST"
//  				,"verify_peer"=>false
//  				,"verify_peer_name"=>false
//  			)
//  	);
//  	return $opts;
//  }
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
function alboError($errno, $errstr, $errfile, $errline) {
	/*
	I seguenti tipi di errore non possono essere gestiti con una funzione definita dall'utente: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING e la maggior parte di E_STRICT generati nel file in cui viene chiamato set_error_handler().
	È importante ricordare che il gestore degli errori PHP standard viene completamente ignorato. Le impostazioni error_reporting() non avranno alcun effetto e il tuo gestore degli errori verrà chiamato comunque, tuttavia sarai comunque in grado di leggere il valore corrente di error_reporting e agire di conseguenza. Di particolare nota è che questo valore sarà 0 se l'istruzione che ha causato l'errore è stata preceduta dall'operatore di controllo dell'errore @.
	*/
	Return "Errore: [$errno] $errstr<br />sorgente: $errfile<br />linea: $errline";
}
?>