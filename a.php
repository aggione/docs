<?php
// https://iam.local/a.php

$s=shell_exec('/usr/bin/perl AB.pl ABGetAccountInfo a.bongiorni');
echo '<br />'.$s;
$a=json_decode($s,true);
if (is_array($a)){
	if (!empty($a['COGNOME'])){
		echo '<li>'.$a['UID'][0].' '.$a['COGNOME'][0].' '.$a['NOME'][0];
	} else {
		echo '<li>'.$a['UID'][0];
	}
} else {
	echo '<li>$a non è un array';
}

$s=shell_exec('/usr/bin/perl AB.pl ABGetAccountInfo a.bongiorniiii');
echo '<br />'.$s;
$a=json_decode($s,true);
if (is_array($a)){
	if (!empty($a['COGNOME'])){
		echo '<li>'.$a['UID'][0].' '.$a['COGNOME'][0].' '.$a['NOME'][0];
	} else {
		echo '<li>'.$a['UID'][0];
	}
} else {
	echo '<li>$a non è un array';
}



?>