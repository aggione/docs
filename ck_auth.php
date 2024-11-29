<?php
// https://dotnetu.local/ldap/ck_auth.php
// https://dotnetu.local/ldap/ck_auth.php?dvl=1
// https://iam.local.santannapisa.it/ck_auth.php

session_start();
if (isset($_SERVER['AJP_eppn'])) {	// shibolet
	$arr = explode("@", $_SERVER['AJP_eppn'], 2);
	$_SESSION['uid_login'] = $arr[0];
} else if (isset($_SERVER['HTTP_EPPN'])) {	// 
	$arr = explode("@", $_SERVER['HTTP_EPPN'], 2);
	$_SESSION['uid_login'] = $arr[0];
} else if (isset($_SERVER['REMOTE_USER'])) {	// 
	$arr = explode("@", $_SERVER['REMOTE_USER'], 2);
	$_SESSION['uid_login'] = $arr[0];
} else if (isset($_SERVER['PHP_AUTH_USER'])) {	// 
	$arr = explode("@", $_SERVER['PHP_AUTH_USER'], 2);
	$_SESSION['uid_login'] = $arr[0];
}
if (isset($_REQUEST['u']) and isset($_REQUEST['p'])) {
	$devel=false;
	if (isset($_SESSION['devel'])){$devel=$_SESSION['devel'];}
} else {
	// inizio
	$devel=false;
	$_SESSION['devel']=false;
	if (isset($_REQUEST['dvl']) and $_SESSION['uid_login']=='a.bongiorni'){
		$devel=true;
		$_SESSION['devel']=true;
	}
}
if ($devel){
	error_reporting(E_ALL);
} else {
	error_reporting(0);	
}
if (isset($_REQUEST['u']) and isset($_REQUEST['p'])){
	$a=array('LDAP','LDAP_GUEST','AD','LDAP_TEST','AD_TEST');
	for ($i=0; $i<count($a); $i++){
		if ($i==0){
			echo '<hr />';
			if ($_SESSION['uid_login']=='a.bongiorni'){echo '<h2>'.$_REQUEST['u'].' '.$_REQUEST['p'].'</h2>';}
		}
		if ($_SESSION['uid_login']=='a.bongiorni' or $i<4){
			if ($i==3){
				echo '<hr />';
			}
			$dettaglio='';
			if(ldap_authenticate($_REQUEST['u'],$_REQUEST['p'],$a[$i])){
				echo get_alert('<strong>Ok!</strong> Autenticato correttamente su '.$a[$i],'success');
			} else {
				echo get_alert('<strong>Ko!</strong> Autenticazione su '.$a[$i].' fallita','danger');
			}
			echo $dettaglio; 
		}
	}
	echo '<hr />';
} else {
	get_home();
}
function ldap_authenticate($username,$password,$lad){
	global $devel, $dettaglio;
	$c=3;		// colonne (1, 2, 3, 4, 6)
	$o=0;
	if ($lad=='LDAP') {
		try {
			$ds = ldap_connect('192.168.64.11',389);	// 192.168.64.112 = Slave ???
		} catch (Exception $e) {
			if ($devel) {echo '<br />Message: ' .$e->getMessage();};
		}
		if ($ds) {
			if ($devel) {echo "<br />LDAP connesso";};
			try {
				ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
				$ldapBind = ldap_bind($ds,'uid='.$username.',ou=Users,o=sss,c=it',$password);
			} catch (Exception $e) {
				if ($devel) {echo '<br />Message: ' .$e->getMessage();};
			}
			if ($ldapBind) {
				$o=1;
				if ($devel) {echo "<br />LDAP autenticato";}
			} else {
				if ($devel) {echo "<br />Credenziali di autenticazione (LDAP) errate";};
			}
			/*	// FILTRI USABILI IN ldap_search
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
			$base="ou=Users,o=sss,c=it";
			// $p='(|(uid=a.bongiorni)(uid=a.signorini))';	// OK
			$p='uid='.$username;	
			// $p='departmentNumber=005730';
			// $p='uid=*';
			// $result=ldap_search($ds, $base, $p, array($cosa));
			$result=ldap_search($ds, $base, $p);
			disp_var($result,'result',$ds);
			if (!is_null($result)) {
				$en = ldap_get_entries($ds, $result);
				$dettaglio=get_dettaglio($en);
			}		
		} else {
			if ($devel) {echo "<br />Non posso connettermi al server di autenticazione LDAP ";}
		}
	}
	if ($lad=='LDAP_GUEST') {
		try {
			$ds = ldap_connect('192.168.64.11',389);
		} catch (Exception $e) {
			if ($devel) {echo '<br />Message: ' .$e->getMessage();};
		}
		if ($ds) {
			if ($devel) {echo "<br />LDAP connesso";};
			try {
				ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
				$ldapBind = ldap_bind($ds,'uid='.$username.',ou=GuestUsers,o=sss,c=it',$password);
			} catch (Exception $e) {
				if ($devel) {echo '<br />Message: ' .$e->getMessage();};
			}
			if ($ldapBind) {
				$o=1;
				if ($devel) {echo "<br />LDAP autenticato";};
			} else {
				if ($devel) {echo "<br />Credenziali di autenticazione (LDAP) errate";};
			}
			$base="ou=GuestUsers,o=sss,c=it";
			$p='uid='.$username;	
			$result=ldap_search($ds, $base, $p);
			disp_var($result,'result',$ds);
			if (!is_null($result)) {
				$en = ldap_get_entries($ds, $result);
				$dettaglio=get_dettaglio($en);
			}					
		} else {
			if ($devel) {echo "<br />Non posso connettermi al server di autenticazione LDAP ";};
		}
	}
	if ($lad=='AD') {
		try {
			$ds = ldap_connect('192.168.64.81',389);
		} catch (Exception $e) {
			if ($devel) {echo '<hr />Message: ' .$e->getMessage();}
		}
		if ($ds) {
			if ($devel) {echo "<hr />ACTIVE DIRECTORY connesso";}
			disp_var($ds,'ds',$ds);
			try {
				ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
				// CN=User Read,OU=Servizi ICT,OU=OpenLdap,DC=sssapisa,DC=it
				// $ldapBind = ldap_bind($ds,'sssapisa.it/OpenLdap/Servizi ICT/Bongiorni Alberto','ABek652wb');	// ok
				$ldapBind = ldap_bind($ds,'sssapisa.it/OpenLdap/Servizi ICT/User Read','readuser');	// ok
				disp_var($ldapBind,'ldapBind',$ds);
				// sssapisa.it/ADprova/U.O. Servizi Prova
				// OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it
				$sr = ldap_search($ds, 'ou=openldap,dc=sssapisa,dc=it', "(userprincipalname=$username@santannapisa.it)");
				// $sr = ldap_search($ds, 'OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it', "(userprincipalname=$username@santannapisa.it)");
				disp_var($sr,'sr',$ds);
			} catch (Exception $e) {
				if ($devel) {echo '<hr />Message: ' .$e->getMessage();};
			}
			if ($sr) {
				try {
					$en = ldap_get_entries($ds, $sr);
					disp_var($en,'en',$ds);
					$nen = ldap_count_entries($ds, $sr);
					disp_var($nen,'nen',$ds);
					if ($devel) {echo "<hr />ACTIVE DIRECTORY trovato ($nen)";};
				} catch (Exception $e) {
					if ($devel) {echo '<hr />Message: ' .$e->getMessage();};
				}
				if (!is_null($en)) {
					$dettaglio=get_dettaglio($en);
				}					
				for ($i=0; $i<$nen; $i++) {
					$upn=$en[$i]["userprincipalname"][0];
					if ($devel) {echo "<hr />ACTIVE DIRECTORY userprincipalname : ($i) $upn";};
					if ($upn==$username.'@santannapisa.it') {
						$cn=$en[$i]["cn"][0];
						$dn=$en[$i]["dn"];
						if ($devel) {echo "<hr />ACTIVE DIRECTORY cn: $cn";};
						if ($devel) {echo "<hr />ACTIVE DIRECTORY dn: $dn";};
						try {
							ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
							ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
							$ldapBind = ldap_bind($ds,$dn,$password);
						} catch (Exception $e) {
							if ($devel) {echo '<hr />Message: ' .$e->getMessage();};
						}
						if ($ldapBind) {
							$o=1;
							if ($devel) {echo "<br />ACTIVE DIRECTORY autenticato";};
						} else {
							if ($devel) {echo "<br />Credenziali di autenticazione (ACTIVE DIRECTORY) errate";};
						}
						break;
					}
				}
			} else {
				if ($devel) {echo "<br />Credenziali di ricerca (ACTIVE DIRECTORY) errate";};
			}
		} else {
			if ($devel) {echo "<br />Non posso connettermi al server di autenticazione ACTIVE DIRECTORY ";};
		}
	}
	if ($lad=='LDAP_TEST') {
		try {
			$ds = ldap_connect('192.168.64.6',389);	
		} catch (Exception $e) {
			if ($devel) {echo '<br />Message: ' .$e->getMessage();};
		}
		if ($ds) {
			if ($devel) {echo "<br />LDAP TEST connesso";};
			try {
				ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
				$p="$password";
				// 							   uid=g.orlandi,ou=Users,o=sss,c=it
				$ldapBind = ldap_bind($ds,'uid='.$username.',ou=Users,o=sss,c=it',$password);
			} catch (Exception $e) {
				if ($devel) {echo '<br />Message: ' .$e->getMessage();};
			}
			if ($ldapBind) {
				$o=1;
				if ($devel) {echo "<br />LDAP TEST autenticato";}
			} else {
				if ($devel) {echo "<br />Credenziali di autenticazione (LDAP TEST) errate";};
			}

//			try {
//				ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
//				ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
//				$p="$password";
//				// 							   uid=g.orlandi,ou=Users,o=sss,c=it
//				$ldapBind = ldap_bind($ds,'uid='.$username.',ou=Users,o=sss,c=it',$p);
//			} catch (Exception $e) {
//				if ($devel) {echo '<br />Message: ' .$e->getMessage();};
//			}
//			if ($ldapBind) {
//				$o=1;
//				if ($devel) {echo "<br />LDAP TEST autenticato";}
//			} else {
//				if ($devel) {echo "<br />Credenziali di autenticazione (LDAP TEST) errate";};
//			}

			$base="ou=Users,o=sss,c=it";
			$p='uid='.$username;	
			$result=ldap_search($ds, $base, $p);
			disp_var($result,'result',$ds);
			if (!is_null($result)) {
				$en = ldap_get_entries($ds, $result);
				$dettaglio=get_dettaglio($en);
			}		
		} else {
			if ($devel) {echo "<br />Non posso connettermi al server di autenticazione LDAP TEST ";}
		}
	}
	if ($lad=='AD_TEST') {
		try {
			$ds = ldap_connect('192.168.64.81',389);
		} catch (Exception $e) {
			if ($devel) {echo '<hr />Message: ' .$e->getMessage();}
		}
		if ($ds) {
			if ($devel) {echo "<hr />ACTIVE DIRECTORY connesso";}
			disp_var($ds,'ds',$ds);
			try {
				ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
				// CN=User Read,OU=Servizi ICT,OU=OpenLdap,DC=sssapisa,DC=it
				// $ldapBind = ldap_bind($ds,'sssapisa.it/OpenLdap/Servizi ICT/Bongiorni Alberto','ABek652wb');	// ok
				$ldapBind = ldap_bind($ds,'sssapisa.it/OpenLdap/Servizi ICT/User Read','readuser');	// ok
				disp_var($ldapBind,'ldapBind',$ds);
				// sssapisa.it/ADprova/U.O. Servizi Prova
				// OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it
				// $sr = ldap_search($ds, 'ou=openldap,dc=sssapisa,dc=it', "(userprincipalname=$username@santannapisa.it)");
				$sr = ldap_search($ds, 'OU=U.O. Servizi Prova,OU=ADprova,DC=sssapisa,DC=it', "(userprincipalname=$username@santannapisa.it)");
				disp_var($sr,'sr',$ds);
			} catch (Exception $e) {
				if ($devel) {echo '<hr />Message: ' .$e->getMessage();};
			}
			if ($sr) {
				try {
					$en = ldap_get_entries($ds, $sr);
					disp_var($en,'en',$ds);
					$nen = ldap_count_entries($ds, $sr);
					disp_var($nen,'nen',$ds);
					if ($devel) {echo "<hr />ACTIVE DIRECTORY trovato ($nen)";};
				} catch (Exception $e) {
					if ($devel) {echo '<hr />Message: ' .$e->getMessage();};
				}
				if (!is_null($en)) {
					$dettaglio=get_dettaglio($en);
				}					

				for ($i=0; $i<$nen; $i++) {
					$upn=$en[$i]["userprincipalname"][0];
					if ($devel) {echo "<hr />ACTIVE DIRECTORY userprincipalname : ($i) $upn";};
					if ($upn==$username.'@santannapisa.it') {
						$cn=$en[$i]["cn"][0];
						$dn=$en[$i]["dn"];
						if ($devel) {echo "<hr />ACTIVE DIRECTORY cn: $cn";};
						if ($devel) {echo "<hr />ACTIVE DIRECTORY dn: $dn";};
						try {
							// ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
							// ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
							$ldapBind = ldap_bind($ds,$dn,$password);
						} catch (Exception $e) {
							if ($devel) {echo '<hr />Message: ' .$e->getMessage();};
						}
						if ($ldapBind) {
							$o=1;
							if ($devel) {echo "<br />ACTIVE DIRECTORY autenticato";};
						} else {
							if ($devel) {echo "<br />Credenziali di autenticazione (ACTIVE DIRECTORY) errate";};
						}
						break;
					}
				}
			} else {
				if ($devel) {echo "<br />Credenziali di ricerca (ACTIVE DIRECTORY) errate";};
			}
		} else {
			if ($devel) {echo "<br />Non posso connettermi al server di autenticazione ACTIVE DIRECTORY ";};
		}
	}
	return $o;
}
function get_dettaglio($en){
	$s='<div style="display:none;">';
	$c=3; // colonne (1, 2, 3, 4, 6)
	$vuoto=array('count'=>0);
	if (!is_null($en) and $en != $vuoto) {
		if (!empty($en[0])){
			if (is_array($en[0])){
				$cw=12/$c;	// larghezza colonne
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
					if (fmod($j,$c) == 0) {$s.='<div class="row py-2">';}
					$s.='<div class="col-sm-'.$cw.'">';
					if (!empty($en[0][$i][0])){
						if ($i=='xscadenzatempo' or $i=='x-scadenzatempo'){
							$d=substr($en[0][$i][0],0,8);
							$d=substr($d,6,2).'/'.substr($d,4,2).'/'.substr($d,0,4);
							$s.=get_alert('Fine rapporto : '.$d,'',true);
						} 
						if ($i=='accountexpires'){
							$d = $en[0][$i][0];
							$winInterval = round($d / 10000000);
							$unixTimestamp = ($winInterval - 11644473600);
							$s.=get_alert('accountexpires (data) : '.date("d/m/Y", $unixTimestamp),'',true);
						}
						if ($i=='dn'){$x=$en[0][$i];} else {$x=$en[0][$i][0];}
						$s.=get_alert($i.' : <strong>'.$x.'</strong>','info',true);
					} else {$s.=get_alert($i,'warning',true);}
					$s.='</div>';		
					if (fmod($j,$c) == ($c-1)) {$s.='</div>';}
				}
				if (fmod($j,$c) < $c) {$s.='</div>';}
			}
		} // else {$s.=print_r($en,true);}
	}			
	$s.='</div>';
	return $s;
}
function disp_var($v,$vn,$link){
	global $devel;
	if (!$devel) {return;}
	echo "<hr><strong>$vn</strong><hr>";
//	echo "var_dump di $vn<br>";
//	var_dump($v);
//	echo "<br>print_r di $vn<br>";
//	print_r($v);
	echo "<br>var_export di $vn<br>";
	var_export($v);
	echo '<br>tipo di $vn = '.gettype($v);
	if (gettype($v)=='array'){
		echo "<br>numero elementi di $vn = ".count($v);
		$k = array_keys($v);
		for ($i=0; $i<count($v); $i++) {
			// to show the attribute displayName (note the case!)
			for ($j=0; $j<count($k); $j++) {
				if (!empty($v[$k[$j]][$i])){
					echo '<br>- '.$k[$j].' = '.$v[$k[$j]][$i];
				} else {
					echo '<br>- '.$k[$j].' = EMPTY';
				}
				// echo '<br>- '.$k[$j].' = '.$v[$k[$j]][$i];
			}
		}					
	}
	if (gettype($v)=='resource'){
		echo "<br>tipo risorsa: ".get_resource_type($v);
		if (get_resource_type($v) == 'ldap result') {
			$errcode = $dn = $errmsg = $refs =  null;
			if (ldap_parse_result($link, $v, $errcode, $dn, $errmsg, $refs)) {
					echo "<br>- errcode<br>";
					var_dump($errcode);
					echo "<br>- dn<br>";
					var_dump($dn);
					echo "<br>- errmsg<br>";
					var_dump($errmsg);
					echo "<br>- refs<br>";
					var_dump($refs);
			}
		}
	}
}
function get_alert($x='',$stl='dark',$small=false){
	if ($small){
		$s='<div class="alert-'.$stl.'" role="alert">'.$x.'</div>';
	} else {
		$s='<div class="alert alert-'.$stl.'" role="alert">'.$x.'</div>';
	}
	return $s;
}
function get_home(){
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="description" content="TEST">
		<meta name="keywords" content="TEST">
		
		<meta name="author" content="Alberto Bongiorni">

		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
		
    <title>TEST</title>
		<style>
			/* stili per la form di login */
			.form-signin {max-width: 330px; padding: 15px; margin: 0 auto;}
			.form-signin .form-signin-heading, .form-signin .checkbox {margin-bottom: 10px;}
			.form-signin .checkbox {font-weight: normal;}
			.form-signin .form-control {position: relative; font-size: 16px; height: auto; padding: 10px; -webkit-box-sizing: border-box; -moz-box-sizing: border-box; box-sizing: border-box;}
			.form-signin .form-control:focus {z-index: 2;}
			.form-signin input[type="text"] {margin-bottom: -1px; border-bottom-left-radius: 0; border-bottom-right-radius: 0;}
			.form-signin input[type="password"] {margin-bottom: 10px; border-top-left-radius: 0; border-top-right-radius: 0;}
			.account-wall{margin-top: 20px; padding: 40px 0px 20px 0px; background-color: #f7f7f7; -moz-box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3); -webkit-box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3); box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3);}
			.login-title{color: #555; font-size: 18px; font-weight: 400; display: block;}
			.profile-img {width: 96px; height: 96px; margin: 0 auto 10px; display: block; -moz-border-radius: 50%; -webkit-border-radius: 50%; border-radius: 50%;}
		</style>
<script type="text/javascript">
$(function() {
	$('#autentica').submit(function(e) {
		e.preventDefault(); 							// ferma l'evento
		$.ajax({
			type:'POST',url:'ck_auth.php',dataType:'text',async:false,data:$('#autentica').serialize(),
			success: function( data ) {
				try {
					$('#risultato').html(data);	// variabile globale 
					$( "div[role='alert']" ).on('click', function(e){
						var t = $(this).next();
						t.toggle();
					});
				} catch (e) {
					// BootstrapDialog.show({title: 'Errore',type: BootstrapDialog.TYPE_DANGER, message: e});
				}
			}
		})
	})
});
</script>
	</head>
	<body>
		<br />
		<div id="contenuto" class="container-fluid">
			<!-- <div class="container"> -->
				<div class="row">
					<div class="col-sm-4"></div>
					<div class="col-sm-4">
						<h1 class="text-center">TEST LDAP AD</h1>
						<div class="account-wall">
							<img class="profile-img" src="LogoCompatto.png" alt="">
							<form id="autentica" class="form-signin">
								<input id="u" type="text" name="u" class="form-control" placeholder="uid (es. a.bongiorni)" required autofocus>
								<input id="p" type="password" name="p" class="form-control" placeholder="Password" required>
								<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
							</form>
						</div>
					</div>
					<div class="col-sm-4"></div>
				</div>
				<div class="row">
					<div class="col-sm-12" id="risultato"></div>
				</div>
			<!-- </div> -->
		</div>
	</body>
</html>
<?php		
}
?>