<?php

function postAuth() {
	
	$url = "https://sssup.prod.up.cineca.it/api/Utenti/login";
	// $token = "generated token code";
	$postData = array(
			'username' => 'ws_up',
			'password' => 'U9!SsSUp2024?',
			'realm'   => '6527fb0aae569a6649f218d1',
	);
	$fields = json_encode($postData);
	$ch = curl_init($url);
	curl_setopt(
				$ch, 
				CURLOPT_HTTPHEADER, 
				array(
						'accept: application/json',
						'Content-Type: application/json',
				)
		);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	$result = curl_exec($ch); 
	curl_close($ch); 
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
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
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
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($Requestbody));
	$result = curl_exec($ch); 
	curl_close($ch); 
	return $result;
}


$res=postAuth();
//echo "$res";
//var_dump($res);
$arr=json_decode($res,true);
$token=$arr['id'];
$result=getLink($token, "6570_N0_1--LEZ", 2023);
echo "$result";



?>
