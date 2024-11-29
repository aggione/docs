<?php

// https://iam.local.santannapisa.it/___up.php

/* 
* Estrapolare i codici esterni delle attività didattiche dall'apiRest
* indicando per ciasuno il codice attività, descrizione attività, l'anno accademico 
* filtrando per codice corso* e anno accademico*
*/	

function getCDS($anno,$corso) {
	/*
curl -X 'GET' \
  'https://sssup.esse3.cineca.it/e3rest/api/offerta-service-v1/offerte?aaOffId=2023&tipiCorsoCod=CO1L' \
  -H 'accept: application/json' \
  -H 'X-Esse3-permit-invalid-jsessionid: true'
*/

	global $devel;
	$e='';
	$url = "https://sssup.esse3.cineca.it/e3rest/api/offerta-service-v1/offerte?aaOffId=$anno&tipiCorsoCod=$corso&statoAttCod=A";
	// $token = "generated token code";
	//$user= 'test';
	//$password= 'S4nn4T3st2022';
	/*$postData = array(
			'username' => 'test',
			'password' => 'S4nn4T3st2022',
	);
	$fields = json_encode($postData);*/
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
						'X-Esse3-permit-invalid-jsessionid: true'
				)
		);
		if ($devel){echo "curl_setopt<br />";}
		$e=error_get_last(); if ($e!=''){echo 'ERROR error_get_last :'.json_encode($e);}
	} catch (Exception $e) {
	  trigger_error('<br>ERRORE curl_setopt: ',json_encode($e));
	}
	try {
		curl_setopt($ch, CURLOPT_HEADER, false);
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
		curl_setopt($ch, CURLOPT_POST, false);
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
		curl_setopt($ch, CURLOPT_HEADER, false);
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
		curl_setopt($ch, CURLOPT_HEADER, false);
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
	
	//echo "$result<br>";
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
		curl_setopt($ch, CURLOPT_HEADER, false);
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
	
	//echo "$result<br>";
	
	return $result;
}

function putOCI($link){
	$u='C##SSS_IMPORT'; $p='ugovimport'; $c='(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.140)(PORT=1521))(CONNECT_DATA=(SID=XE)))'; 
	$conn_new = oci_pconnect($u,$p,$c,'AL32UTF8');	// in utf8
	if (empty($conn_new)) {
		echo '<h2>problemi di connessione al db ORACLE di interscambio</h2>';;
		exit;
	}
	$sql = "ALTER SESSION SET NLS_DATE_FORMAT = 'DD/MM/YYYY HH24:MI:SS'";
	$p = oci_parse($conn_new, $sql);
	$e = oci_execute($p);
	foreach($link as $ox){
		$sql='insert into c##sss_didattica.links_ale (AAOFFID, CDSCOD, PROGR, EXTCODE, CODICEAF, NOME, NOME_EN, LINK) values (';
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
	}
	$out[]=$ox;
	if (!empty(ob_get_level())){flush(); ob_flush();}

}

function getUP($anno){
@set_time_limit(0); // tempo illimitato

//$anno=2023;

$cds=getCDS($anno,"CO1L");
$arr_cds1=json_decode($cds,true);
$cds=getCDS($anno,"CO2L");
$arr_cds2=json_decode($cds,true);
$cds=getCDS($anno,"COCU5");
$arr_cds3=json_decode($cds,true);
$cds=getCDS($anno,"COCU6");
$arr_cds4=json_decode($cds,true);
$cds=getCDS($anno,"D2");
$arr_cds5=json_decode($cds,true);
$cds=getCDS($anno,"D226");
$arr_cds6=json_decode($cds,true);
$cds_arr=array_merge($arr_cds1,$arr_cds2,$arr_cds3,$arr_cds4,$arr_cds5,$arr_cds6);

$r=postAuth();
$arr=json_decode($r,true);
$token=$arr['id'];


$calendario=array();

// FOR CDS
foreach($cds_arr as $jk => $ji){
	
	$cds=$ji['cdsCod'];
	$extcode=getExtCode($token,$cds,$anno);
	$arr_extcode=json_decode($extcode,true);
	
	foreach($arr_extcode as $jke => $jie){
	
		$tf=false;
		$jie['codiceAF']='';
		$jie['nome']='';
		$jie['nome_en']='';
		foreach($jie['dettagliDidattici'] as $jiddi){
			
			if (!empty($jiddi['codiceAF']) and $tf===false){
				//echo ' - CodiceAF: '.$jiddi['codiceAF'];
				$jie['codiceAF']=$jiddi['codiceAF'];
				//echo ' - nome: '.$jiddi['nome'];
				$jie['nome']=$jiddi['nome'];
				$jie['nome_en']=$jiddi['nome_EN'];
				//if (!empty($jie['extCode'])){echo $jie['extCode']."<br>";}
				$tf=true;
				
				
				
			}
		}
		$link="";
		//$jie['extCode']="";
		if (!empty($jie['extCode'])){
			$link=getLink($token, $jie['extCode'], $anno);
			$link=json_decode($link,true);
			}
			$element=array();
			//$element['extCode']=$jie['extCode'];
			$element['cdsCod']=$cds;
			$element['anno']=$anno;
			$element['link']=$link;
			$element['codiceAF']=$jie['codiceAF'];
			$element['nome']=$jie['nome'];
			$element['nome_en']=$jie['nome_en'];
			$element['anno']=$anno;
			//echo " $link  <br><br>";
			array_push($calendario,$element);	
	}
	
	
	
	
}
return $calendario;
}


$anno=date("Y")-1;
$calendario=getUP($anno);
echo json_encode($calendario);
/*foreach ($calendario as $element){
	$link=$element['link'];
	$nome=$element['nome'];
	$codiceAF=$element['codiceAF'];
	$cdsCod=$element['cdsCod'];
	$anno=$element['anno'];
	echo "$anno $codiceAF $cdsCod $nome $link<br/>";
}*/

?>
