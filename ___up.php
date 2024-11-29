<?php

// https://iam.local.santannapisa.it/___up.php

/* 
* Estrapolare i codici esterni delle attività didattiche dall'apiRest
* indicando per ciasuno il codice attività, descrizione attività, l'anno accademico 
* filtrando per codice corso* e anno accademico*
*/	

$e='';
$devel=true;
$devel=false;
set_error_handler("alboError");
@set_time_limit(0); // tempo illimitato

$u='C##SSS_IMPORT'; $p='ugovimport'; $c='(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.140)(PORT=1521))(CONNECT_DATA=(SID=XE)))'; 
$conn_new = oci_pconnect($u,$p,$c,'AL32UTF8');	// in utf8
if (empty($conn_new)) {
	echo '<h2>problemi di connessione al db ORACLE di interscambio</h2>';;
	exit;
}
$sql = "ALTER SESSION SET NLS_DATE_FORMAT = 'DD/MM/YYYY HH24:MI:SS'";
$p = oci_parse($conn_new, $sql);
$e = oci_execute($p);

if ($devel){echo 'start<br />';}
$r=postAuth();
$sr=json_encode($r,JSON_PRETTY_PRINT);
if ($devel){echo str_replace("\n",'<br />',$sr);}
$from=strpos($sr,"{");
$to=strripos($sr,"}");
if (is_null($from)){$from=0;}
if (is_null($to)){$to = strlen($sr);}
$sj=substr($sr,$from,($to-$from+1));
if ($devel){echo '<hr>da: '.$from.' - to: '.$to;}
if ($devel){echo '<br>'.$sj;}
try {
	$sj=str_replace('\"','"',$sj);
	$j=json_decode($sj,true);
	if ($devel){echo "<br>json_decode";}
	if ($devel){echo "<br>".gettype($j);}
	$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
} catch (Exception $e) {
	trigger_error('<br>ERRORE json_decode: ',json_encode($e));
}
if (is_object($j) or is_array($j)){
	if ($devel){echo '<br>object or array';}
	$sr=json_encode($j,JSON_PRETTY_PRINT);
	if ($devel){echo '<hr>'.str_replace("\n",'<br />',$sr);}

	$token=$j['id'];
	echo '<hr>id: '.$token;

	$out=array(); $oute=array();

	$sql="select * from c##sss_didattica.cds where AAOFFID IN (".(date("Y")-1).",".date("Y").") AND TIPOCORSOCOD IN ('CO1L','CO2L','COCU5','COCU6','D2','D226')";
	$r=oraexec($sql,'m');
	if (empty($r['error'])){
		echo '<br />'.$r['nc'].' colonne - '.$r['nr'].' righe';
		if ($r['nc']>0 and $r['nr']>0){
			$rr=oraexec('truncate table c##sss_didattica.links');
			echo "<br>".$r['nc'].' colonne - '.$r['nr'].' righe';
			for ($i=0; $i<count($r['result']); $i++){
				// if ($i<1){
					$c=$r['result'][$i]['CDSCOD'];
					// $c=str_replace('/','',$c); 
					$y=$r['result'][$i]['AAOFFID'];
					$x=getExtCode($token,$c,$y);
					echo "<hr>";
					echo '<br /><strong>'.$c.' '.$y.'</strong>';
					// echo '<br />'.gettype($x);
					// echo '<br />'.json_encode($x);
					$from=strpos($x,"[");
					$to=strripos($x,"]");
					if (is_null($from)){$from=0;}
					if (is_null($to)){$to = strlen($x);}
					$sx=substr($x,$from,($to-$from+1));
					$j=json_decode($sx,true);
					echo '<br />'.gettype($j);
					foreach($j as $jk => $ji){
						$ox=array('AAOFFID'=>$y,'CDSCOD'=>$c,'progr'=>$jk);
						if (!empty($ji['extCode'])){
							echo '<br />extCode: '.$ji['extCode'];
							$ox['extCode']=$ji['extCode'];
						} else {
							echo '<br><span style="color:red;">'.json_encode($ox).'</span>';
						}
						$tf=false;
						$ox['codiceAF']='';
						$ox['nome']='';
						$ox['nome_en']='';
						foreach($ji['dettagliDidattici'] as $jiddi){
							if (!empty($jiddi['codiceAF']) and $tf===false){
								echo ' - CodiceAF: '.$jiddi['codiceAF'];
								$ox['codiceAF']=$jiddi['codiceAF'];
								echo ' - nome: '.$jiddi['nome'];
								$ox['nome']=$jiddi['nome'];
								$ox['nome_en']=$jiddi['nome_EN'];
								$tf=true;
							}
						}
						$link='';
						if (!empty($ji['extCode'])){
							$link=getLink($token, $ji['extCode'], $y);
							$from=strpos($link,'"https');
							$to=strripos($link,'"');
							if (is_null($from)){$from=0;}
							if (is_null($to)){$to = strlen($link);}
							$link=substr($link,$from+1,($to-$from)-1);
						}
						echo ' - link: <strong>'.$link.'</strong>';
						$ox['link']=$link;

						if (!empty($link)){
							$sql='insert into c##sss_didattica.links (AAOFFID, CDSCOD, PROGR, EXTCODE, CODICEAF, NOME, NOME_EN, LINK) values (';
							$sql.="".intval($ox['AAOFFID'])."";
							$sql.=", '".$ox['CDSCOD']."'";
							$sql.=", ".$ox['progr']."";
							$sql.=", '".$ox['extCode']."'";
							$sql.=", '".$ox['codiceAF']."'";
							$sql.=", '".str_replace("'","''",$ox['nome'])."'";
							$sql.=", '".str_replace("'","''",$ox['nome_en'])."'";
							$sql.=", '".$ox['link']."'";
							$sql.=')';
							$rr=oraexec($sql);

							$out[]=$ox;
						}	else {
							$oute[]=$ox;
						}
						if (!empty(ob_get_level())){flush(); ob_flush();}
				}
			}
			file_put_contents('___up.json',json_encode($out,JSON_PRETTY_PRINT), LOCK_EX); // FILE_APPEND
			chmod('___up.json', 0777);
			file_put_contents('___up_err.json',json_encode($oute,JSON_PRETTY_PRINT), LOCK_EX); // FILE_APPEND
			chmod('___up_err.json', 0777);
		}
	} else {
		echo '<br />'.$r['error'];
	}
} else {
	echo '<br />tipo j: '.gettype($j);
}

/*Generazione token di autenticazione 
*curl -X 'POST' \ 'https://cineca.prod.up.cineca.it/api/Utenti/login' \ -H 'accept: application/json' \ -H 'Content-Type: application/json' \ -d '{ "username": "ws_up", "password": "U9!SsSUp2024?", "realm": "6527fb0aae569a6649f218d1" }'
*/
function alboError($errno, $errstr, $errfile, $errline){
	echo "ERRORE: (n: $errno) (e: $errstr) (f: $errfile) (l: $errline)<br />";
}
function postAuth() {
	global $devel;
	$e='';
	$url = "https://sssup.prod.up.cineca.it/api/Utenti/login";
	// $token = "generated token code";
	$postData = array(
			'username' => 'ws_up',
			'password' => 'U9!SsSUp2024?',
			'realm'   => '6527fb0aae569a6649f218d1',
	);
	$fields = json_encode($postData);
	try {
		$ch = curl_init($url);
		if ($devel){echo "curl_init<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_init: ',json_encode($e));
	}
	try {
		curl_setopt(
				$ch, 
				CURLOPT_HTTPHEADER, 
				array(
						'accept: application/json',
						'Content-Type: application/json',
				)
		);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_HEADER, true);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_POST, true);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}

	// Execute the request and get the response
	try {
		$result = curl_exec($ch); 
		if ($devel){echo "curl_exec<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_exec: ',json_encode($e));
	}

	// Close the cURL handle
	try {
		curl_close($ch); 
		if ($devel){echo "curl_close<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_close: ',json_encode($e));
	}

	// echo "$result"; Output the response

	//@@@@ prendo solo il token per inserirlo nella variabile
	return $result;
}
function getExtCode($token, $cdsCod, $year){
/*
Curl
curl -X 'POST' \ 'https://sssup.prod.up.cineca.it/api/Eventi/getEventiByCorso' \ -H 'accept: application/json' \ -H 'Authorization: O8Z7zmy2WMZi0eYzS0adAPQ5XlbCMTaTU6eTuv4e7pimdSCksW4Jygfsm7jCaJvc' \ -H 'Content-Type: application/json' \ -d '{ "codCorso": "A/I1L/3", "annoAccademico": 2023 }'

Request URL
https://sssup.prod.up.cineca.it/api/Eventi/getEventiByCorso
*/	
	global $devel;
	$e='';
	
	$url_extcode = "https://sssup.prod.up.cineca.it/api/Eventi/getEventiByCorso";
	$Requestbody = array(
			'codCorso'=> $cdsCod,
			'annoAccademico'=> intval($year),
	);
	$ch = curl_init($url_extcode);
	curl_setopt(
		$ch, 
		CURLOPT_HTTPHEADER, 
		array(
			'accept: application/json',
			'Content-Type: application/json', // se il content type è json
			// 'bearer: '.$token  //se necessitiamo di un token di autorizzazione nell'header
			'Authorization: '.$token  //se necessitiamo di un token di autorizzazione nell'header
		)
	);
	try {
		curl_setopt($ch, CURLOPT_HEADER, true);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_POST, true);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($Requestbody));
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	// Execute the request and get the response
	try {
		$result = curl_exec($ch); 
		if ($devel){echo "curl_exec<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_exec: ',json_encode($e));
	}

	// Close the cURL handle
	try {
		curl_close($ch); 
		if ($devel){echo "curl_close<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_close: ',json_encode($e));
	}

	// echo "$result"; Output the response

	//@@@@ prendo solo il token per inserirlo nella variabile
	return $result;
}
function getLink($token, $extCod, $year){
/*
Curl

curl -X 'POST' \ 'https://sssup.prod.up.cineca.it/api/LinkCalendario/getCalendarioDaListaEventi' \ -H 'accept: application/json' \ -H 'Authorization: rak3TNA9MLBpKqUj98xupvYATGFuzOe0FjsHPvkmrUDA4sIZMpCNLhX3FuIRnCuN' \ -H 'Content-Type: application/json' \ -d '{ "listaEventi": [ "6536_N0_1--LEZ" ], "defaultVista": "lista", "defaultFormato": "mese", "daData": "2023-01-01" }'
Request URL
https://sssup.prod.up.cineca.it/api/LinkCalendario/getCalendarioDaListaEventi

						{
							"listaEventi": [
								"155604--LEZ"
							],
							"defaultVista": "lista",
							"defaultFormato": "mese",
							"daData": "2023-01-01"
						}
*/
	
	global $devel;
	$e='';
	
	$url_extcode = "https://sssup.prod.up.cineca.it/api/LinkCalendario/getCalendarioDaListaEventi";
	$Requestbody = array(
			'listaEventi' => array($extCod),
			'defaultVista' => 'lista',
			'defaultFormato' => 'mese',
			'daData' => $year.'-01-01'
	);
	$ch = curl_init($url_extcode);
	curl_setopt(
		$ch, 
		CURLOPT_HTTPHEADER, 
		array(
			'accept: application/json',
			'Content-Type: application/json', // se il content type è json
			// 'bearer: '.$token  //se necessitiamo di un token di autorizzazione nell'header
			'Authorization: '.$token  //se necessitiamo di un token di autorizzazione nell'header
		)
	);
	try {
		curl_setopt($ch, CURLOPT_HEADER, true);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_POST, true);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($Requestbody));
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	// Execute the request and get the response
	try {
		$result = curl_exec($ch); 
		if ($devel){echo "curl_exec<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_exec: ',json_encode($e));
	}

	// Close the cURL handle
	try {
		curl_close($ch); 
		if ($devel){echo "curl_close<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_close: ',json_encode($e));
	}

	// echo "$result"; Output the response

	//@@@@ prendo solo il token per inserirlo nella variabile
	return $result;
}
function oraexec($sql,$t='o'){
	global $conn_new, $devel;
	$result=array();
	if ($sql==''){
		return array('error'=>'SQL vuoto');
	} else {
		$result['sql']=$sql;
	}
	$p = @oci_parse($conn_new, $sql);
	if ($p){
		$x = @oci_execute($p);
	} else {
		$e = oci_error($conn_new); 
		$result['error']=json_encode($e);
		return $result;
	}
	$e = oci_error($conn_new); 
	if (!empty($e)){
		$result['error']=json_encode($e);
		return $result;
	}
	$a=explode(" ",strtolower($sql));
	if (!in_array($a[0],array('select','insert','update','delete'))){
		$result['error']='Query non ammessa';
		return $result;
	}
	if ($a[0]=='select'){
		if ($t=='o'){$result['nr'] = oci_fetch_all($p, $r);}
		else {$result['nr'] = oci_fetch_all($p, $r, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);}
		$result['nc']=oci_num_fields($p);
		$result['result']=$r;
	} else {
		$result['nr'] = oci_num_rows($p);
	}
	return $result;
}
?>
