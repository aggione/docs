<?php
// https://iam.local/check_forwarder.php
echo '<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="description" content="check forwarder">
		<meta name="keywords" content="check forwarder">
		<meta name="author" content="Alberto Bongiorni">

		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
		
		<title>check forwarder</title>
	</head>
	<body>	

		<div class="container" id="contenuto">
			<div class="row">
				<div class="col-sm text-center alert alert-info">
					<h1>Check forwarder</h1>
				</div>
			</div>
		</div>

	</body>
</html>
';	
if (!empty(ob_get_level())){flush(); ob_flush();}		

$u='C##SSS_IMPORT'; $p='ugovimport'; $c='(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.140)(PORT=1521))(CONNECT_DATA=(SID=XE)))'; 
$conn_new = oci_pconnect($u,$p,$c,'AL32UTF8');	// in utf8
$e = oci_error();
if (empty($conn_new) or $e!='') {
	trigger_error('non ho la connessione a XE', E_USER_ERROR);
	exit;
}
$sql = "ALTER SESSION SET NLS_DATE_FORMAT = 'DD/MM/YYYY HH24:MI:SS'";
$p = oci_parse($conn_new, $sql);
$e = oci_execute($p);
$e = oci_error();

$sql="SELECT * FROM C##SSS_IMPORT.AB_CAN where ldap_uid is not null";
$x=oraexec($sql);
if (empty($x['error']) and !empty($x['nr']) and !empty($x['nc'])){$ab_can=$x['result'];} else {exit;}

$uu='storico_ldap'; $pp='Ld4pSt0r1c0'; $cc='mysql'; 
$conn_sl = mysqli_connect($cc, $uu, $pp);	
mysqli_select_db($conn_sl,'storico_ldap'); //or die('Could not select database');
	
$sql="select * from storico_ldap.richieste_account where ra_cf is not null and ra_uid is not null order by ra_cognome, ra_nome";
$a=mysqli_query($conn_sl,$sql);
if (!empty(mysqli_error($conn_sl))){
	$e.=get_alert(mysqli_error($conn_sl),'danger');
}
$j=array();
if (mysqli_num_rows($a) > 0) {foreach($a as $row){$j[]=$row;}}

$s='<div class="row">';
	$s.='<div class="col-sm-9 text-center alert alert-primary">cognome nome cf</div>';
	$s.='<div class="col-sm text-center alert alert-success">forward</div>';
	$s.='<div class="col-sm text-center alert alert-success">forward alias</div>';
	$s.='<div class="col-sm text-center alert alert-success">account</div>';
$s.='</div>';
disp($s.'<ol>','contenuto',true);
foreach ($j as $ji){
	$stl='warning'; $ct=''; $fo=''; $ffo='';
	$haf=false; $hafa=false; $haa=false;
	if (in_array($ji['ra_uid'],$ab_can['LDAP_UID']) !== false){ // solo gli attivi
		$ffo=$ji['ra_uid'];
		$fo = shell_exec('/usr/bin/perl AB.pl ABGetForwarder '.strtolower($ffo));
		if (!empty($fo)){
			$haf=true;
		} else {
			if (!empty($ji['ra_ad_princ_mail'])){
				$a=explode('@',$ji['ra_ad_princ_mail']);	$ffo=$a[0];
				$fo = shell_exec('/usr/bin/perl AB.pl ABGetForwarder '.strtolower($ffo));
				if (!empty($fo)){$hafa=true;}
			}
		}
		if (empty($fo)){
			// non esiste il forwarder cerco se ha l'account
			$fo = shell_exec('/usr/bin/perl AB.pl ABGetAccountInfo '.strtolower($ji['ra_uid']));
			try{$a=@json_decode($fo,true);} catch (Throwable $ee) {}
			if (is_array($a)){
				$fo = strtolower($a['NOME'][0].'.'.$a['COGNOME'][0]).'@alumnisssup.mail.onmicrosoft.com';
				$haa=true;
			}
		}
		if (empty($fo)){
			$stl='danger';
		} else {
			$stl='success';
			if ($fo != strtolower($ji['ra_ad_princ_mail']).'@alumnisssup.mail.onmicrosoft.com'){
				$ct='text-primary';
			}		
		}
	}
	if (is_array($fo)){$fo=json_encode($fo);}
	$s='<div class="row">';
		$s.='<div class="col-sm-9 text-center alert alert-'.$stl.'">';
			$s.='<li class="'.$ct.'">'.$ji['ra_cognome'].' '.$ji['ra_nome'].' '.$ji['ra_cf'].' '.$fo.'</li>';
		$s.='</div>';
		if ($haf){$s.='<div class="col-sm text-center alert alert-success">o</div>';} else {$s.='<div class="col-sm></div>';}
		if ($hafa){$s.='<div class="col-sm text-center alert alert-success">o</div>';} else {$s.='<div class="col-sm></div>';}
		if ($haa){$s.='<div class="col-sm text-center alert alert-success">o</div>';} else {$s.='<div class="col-sm></div>';}
	$s.='</div>';
	// disp(get_alert('<li class="'.$ct.'">'.$ji['ra_cognome'].' '.$ji['ra_nome'].' '.$ji['ra_cf'].' '.$fo.'</li>',$stl),'contenuto',true);
	disp($s,'contenuto',true);
}
disp('</ol>','contenuto',true);
//------------------------------------------------------
function disp($m,$dom,$append=false){
	$m=str_replace("'","\'",$m);
	$m=str_replace('"','\"',$m);
	$s = '<script type="text/javascript" temp="1">if ($("#'.$dom.'").length > 0){';
	if ($append){
		$s.='$("#'.$dom.'").append("'.$m.'");';
	} else {
		$s.='$("#'.$dom.'").html("'.$m.'");';
	}
	$s .='} 
	$("[temp]").remove(); 
	</script>';
	echo $s;
	if (!empty(ob_get_level())){flush(); ob_flush();}
}
function get_alert($x='',$stl='dark',$small=false){
	$s='';
	if ($small){
		$s.='<div class="alert-'.$stl.'">'.$x.'</div>';
	} else {
		$s.='<div class="row py-1 alert alert-'.$stl.'"><div class="col-sm">'.$x.'</div></div>';		
	}
	return $s;
}
function oraexec($sql,$t='o'){
	global $conn_new;
	$e='';
	$r=array();
	if (empty($sql)){
		$r['error']='SQL mancante';
		return $r;
	}
	$a=explode(" ",strtolower($sql));
	$ret=false; if ($a[0]=='select'){$ret=true;}
	$p = oci_parse($conn_new, $sql);
	if ($p){
		try {
			$x = oci_execute($p);
		} catch (Throwable $ee) {
			$e .= json_encode($ee,json_pretty_print);
		}
	}
	$ee = oci_error($conn_new); if (!empty($ee)){$e .= json_encode($ee,json_pretty_print);}
	$ee = error_get_last();			if (!empty($ee)){$e .= json_encode($ee,json_pretty_print);}		
	if ($ret and empty($e)){
		if ($a[0]=='select'){
			if ($t=='o'){
				$r['nr'] = oci_fetch_all($p, $result);	// tradizionale oracle
			} else {
				$r['nr'] = oci_fetch_all($p, $result, 0, 0, OCI_FETCHSTATEMENT_BY_ROW);	// stile mysql
			}
			$r['nc']=oci_num_fields($p);
			$r['result']=$result;
		} else {
			$r['nr'] = oci_num_rows($p);
		}
	}
	if (!empty($e)){$r['error']=$e;}
	return $r;
}

?>