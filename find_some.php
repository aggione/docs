<?php
/*
	c = termine di ricerca (obbligatorio)
	d = dir da cui cominciare la ricerca (default = /opt/html)
	f = lista di estensioni separate da , (default = php)
	t = livello di dettaglio (0 = nome file (default), 1 = nome e righe, 2 = nome, righe e contenuto riga)

	esempi:
	-----------------------------------------------------------------------------------
	https://iam.local.santannapisa.it/find_some.php
	https://iam.local.santannapisa.it/find_some.php?c=ldap_mod_replace
	https://iam.local.santannapisa.it/find_some.php?c=CMG_BKP
	https://iam.local.santannapisa.it/find_some.php?c=SSS_TABLES
	https://iam.local.santannapisa.it/find_some.php?c=SSS_CS&t=0
	https://iam.local.santannapisa.it/find_some.php?c=Alberto&f=log,php
	https://iam.local.santannapisa.it/find_some.php?c=alumni&f=csv&d=/opt/html/ldap/files&t=2
	
	comandi linux (ksh)
	-----------------------------------------------------------------------------------
	grep -inr --include \*.php "SSS_IMMOBILI" /opt/html
	grep -inr --include \*.php --include \*.css --include \*.js "SSS_BLOB" ~/opt/html
*/
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="description" content="Find">
		<meta name="keywords" content="Find">
		<meta name="author" content="Alberto Bongiorni">
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
		
    <title>Find</title>
	</head>
	<body>
	<div class="container-fluid">
<?php

	if (empty($_REQUEST['f'])){$_REQUEST['f']='php';}	// default
	if (!empty($_REQUEST['f'])){
		$af=explode(',',$_REQUEST['f']);
		$f=' --include \\*.'.implode(' --include \\*.',$af);
	}
	$dirh=__DIR__;
	if (empty($_REQUEST['d'])){$d=$dirh;} else {$d=$_REQUEST['d'];}
	if (empty($_REQUEST['t'])){$t=0;}	else {$t=$_REQUEST['t'];}
	$c='';
	if (!empty($_REQUEST['c'])){$c=str_replace('"','\"',$_REQUEST['c']);}

	$s='<form id="parametri" action="'.$_SERVER['PHP_SELF'].'">
			<div class="row">
				<div class="col-sm-12">'.get_alert("Inserisci i parametri di ricerca","dark text-center",true).'</div>
			</div>
			<div class="row">
				<div class="col-sm-3 text-right"><label for="c">cosa : </label></div>
				<div class="col-sm-9"><input type="text" name="c" id="c" value="'.$c.'" class="form-control form-control-sm" /></div>
			</div>
			<div class="row">
				<div class="col-sm-3 text-right"><label for="d">dir : </label></div>
				<div class="col-sm-9"><input type="text" name="d" id="d" value="'.$d.'" class="form-control form-control-sm" /></div>
			</div>
			<div class="row">
				<div class="col-sm-3 text-right"><label for="f">estensioni (separate da ,) : </label></div>
				<div class="col-sm-9"><input type="text" name="f" id="f" value="'.implode(',',$af).'" class="form-control form-control-sm" /></div>
			</div>
			<div class="row">
				<div class="col-sm-3 text-right"><label for="t">dettaglio (0, 1, 2) : </label></div>
				<div class="col-sm-9"><input type="text" name="t" id="t" value="'.$t.'" class="form-control form-control-sm" /></div>
			</div>
			<div class="row">
				<div class="col-sm-12"><button class="btn btn-success btn-block" type="submit">Cerca</button></div>
			</div>
		</form>
		<hr />';
	echo $s;
	if (!empty(ob_get_level())){flush(); ob_flush();}

	if (!empty($_REQUEST) and $c==''){
		echo get_alert('Includi un termine di ricerca','danger');
		if (!empty(ob_get_level())){flush(); ob_flush();}
		exit;
	}

	$comando='grep -inr '.$f.' "'.$c.'" '.$d;
	echo get_alert($comando.' - Cerca <strong>'.$c.'</strong> nei files con estensione: <strong>'.implode(',',$af).'</strong>');
	if (!empty(ob_get_level())){flush(); ob_flush();}
	$s=shell_exec($comando);
	$as=explode("\n",$s);
	if (is_array($as)){
		echo (count($as)-1).' lines';
		$dep='';
		echo '<table class="table table-striped table-sm table-responsive">';
		foreach($as as $r){
			$ar=explode(":",$r);
			$s='';
			for($i=0; $i<count($ar); $i++){
			// foreach($ar as $fld){
				if ($i==0){
					if ($ar[0]==$dep){
						$s.='<td></td>';
					} else {
						if (substr($ar[$i],0,strlen($dirh)) == $dirh){
							$s.='<td><a target="_blank" href="ftp://dotnetu.local/'.$ar[$i].'">'.$ar[$i].'</a></td>';
						} else {
							$s.='<td>'.$ar[$i].'</td>';
						}
					}
				} else {
					if ($t >= $i){
						$s.='<td>'.$ar[$i].'</td>';
					}
				}
			}
			if ($s!='' and $s!='<td></td>'){echo '<tr>'.$s.'</tr>';}
			$dep=$ar[0];
		}
		echo '</table>';
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
?>
		</div>
	</body>
</html>
