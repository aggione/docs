<?php
// https://iam.local.santannapisa.it/____info.php

	echo '<hr /><h1 style="text-align:center; color:red;"/>IAM</h1>';

	echo '<div style="margin:auto; width:50%; border:3px solid green; padding:10px;"><table>';
	echo '<tr><td style="text-align:right;">__FILE__:</td><td style="color:blue;"/>'.__FILE__.'</td></tr>';
	echo '<tr><td style="text-align:right;">dirname(__FILE__):</td><td style="color:blue;"/>'.dirname(__FILE__).'</td></tr>';
	echo '<tr><td style="text-align:right;">__DIR__:</td><td style="color:blue;"/>'.__DIR__.'</td></tr>';
	echo '<tr><td style="text-align:right;">$_SERVER[\'PHP_SELF\']:</td><td style="color:blue;"/>'.$_SERVER['PHP_SELF'].'</td></tr>';
	echo '<tr><td style="text-align:right;">realpath(dirname(__FILE__)):</td><td style="color:blue;"/>'.realpath(dirname(__FILE__)).'</td></tr>';
	echo '<tr><td style="text-align:right;">getcwd():</td><td style="color:blue;"/>'.getcwd().'</td></tr>';
	echo '</table></div>';	
	
	$cmd = 'set';
	echo '<h1 style="text-align:center; color:red;"/>SET</h1>';
	$r=shell_exec($cmd);
	$a=explode("\n",$r);
	echo '<div style="margin:auto; width:50%; border:3px solid green; padding:10px;"><table>';
	for ($i=0; $i<count($a); $i++){
		if (strpos($a[$i],'=')!==false){
			$aa=explode("=",$a[$i]);
			if ($i<(count($a) -1)){if (strpos($a[$i+1],'=')===false){$aa[1].=$a[$i+1];}}
			echo '<tr><td style="text-align:right;">'.$aa[0].'</td><td style="color:blue;"/>'.$aa[1].'</td></tr>';
		}
	}
	echo '</table></div>';	
	// echo '<hr><div style="text-align:center; color:blue;"/><pre>'.shell_exec($cmd).'</pre></div>';
	
	echo '<hr><h1 style="text-align: center;color:red;">Php</h1><hr>';
	phpinfo();
?>