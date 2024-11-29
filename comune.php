<?PHP
function mail_albo($mittente, $destinatario, $oggetto, $messaggio, $allegato=''){

	$m='Applicazione: '.getcwd() . ' (' . __FILE__ . ')';
	$m.='<br />Data e ora: '.date("d/m/Y H:i:s");
	$m.='<br />IP: '.getIpAddress();;
	$m.='<br />uid: '.(empty($_SESSION['uid_login'])?'######':$_SESSION['uid_login']);
	$m.='<br />'."\r\n";
	$messaggio.=$m;

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
	} else {
		tolog('Errore invio mail! '."\n");
	}
}
function ab_write_file($path_name,$contenuto,$append=false){
	$ammessi = array(".txt", ".csv", ".log", ".pdf");
	if (in_array(substr($path_name,-4), $ammessi)) {
		if (!file_exists($path_name)) {	// creo il file se non esiste
			$myfile = fopen($path_name,"w") or die("___");
			fwrite($myfile,"");
			fclose($myfile);
			chmod($path_name, 0777);
		};	
		if($append){
			file_put_contents($path_name, $contenuto, FILE_APPEND | LOCK_EX);
		} else {
			file_put_contents($path_name, $contenuto, LOCK_EX);
		}
	}
}
function checklog($app){
	// else {$logf='/var/www/html/_comuni/log/'.$app.'_'.date("Ym").'.log';}
	$logf='../log/'.$app.'_'.date("Ym").'.log';
	if (!file_exists($logf)) {	// creo il file di log se non esiste
		$myfile = fopen($logf,"w") or die("Unable to open file!");
		fwrite($myfile,"");
		fclose($myfile);
		chmod($logf, 0777);
		tolog("creato il file di log $logf");
	};
	return $logf;
}
function to_json($o){
	global $logf;
	$r=json_encode($o);
	$e=json_last_error();
	switch ($e) {
		case JSON_ERROR_NONE:	// No error has occurred 	 
			break;
		case JSON_ERROR_DEPT:	
			file_put_contents($logf,"--- JSON The maximum stack depth has been exceeded\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_STATE_MISMATCH:
			file_put_contents($logf,"--- JSON Invalid or malformed JSON\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_CTRL_CHAR:
			file_put_contents($logf,"--- JSON Control character error, possibly incorrectly encoded\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_SYNTAX:
			file_put_contents($logf,"--- JSON Syntax error\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_UTF8:
			file_put_contents($logf,"--- JSON Malformed UTF-8 characters, possibly incorrectly encoded\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_RECURSION:
			file_put_contents($logf,"--- JSON One or more recursive references in the value to be encoded\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_INF_OR_NAN: 	
			file_put_contents($logf,"--- JSON One or more NAN or INF values in the value to be encoded\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_UNSUPPORTED_TYPE:
			file_put_contents($logf,"--- JSON A value of a type that cannot be encoded was given\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_INVALID_PROPERTY_NAME:
			file_put_contents($logf,"--- JSON A property name that cannot be encoded was given\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_UTF16:
			file_put_contents($logf,"--- JSON Malformed UTF-16 characters, possibly incorrectly encoded\r\n", FILE_APPEND );
			break;
	}
	return $r;
}
function getconn($u,$p,$c,$t,$db=''){	
	// ritorna una connessione permanente 	
	// 		esempio: $conn_UGOV_SIAIE=getconn('SIAIE_SSSUP_PROD','p3n6w0xa!',$ao['UGOV'],'o');
	// 			oci_pconnect( "utente", "password", "easy o SID (TNS_ENTRY)");
	// 			oci_pconnect('hr', 'welcome', 'localhost/XE');
	// 			oci_pconnect( string $username , string $password [, string $connection_string [, string $character_set [, int $session_mode ]]] )
	// 				session_mode = OCI_DEFAULT, OCI_SYSOPER and OCI_SYSDBA.
	// global $logf;
	global $devel;
	$err_level=error_reporting();
	error_reporting(0);
	$e='';
	switch ($t) {
		case 'o':	
			// oracle
			$a = array(	// array di connessioni per oracle
				'CIA1'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=ccscia.sssup.it)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=CIA1)))'
				,'SISDB'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=sisdb.local)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=SISSSS)))'
				,'SISDBTEST'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=sisdbtest.local)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=SISTEST)))'
				,'DOTNET'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=dotnet.local)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=LORADOTN)))'
				,'SSS'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.119)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=SSS)))'
				,'DW'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.33)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=DWSSSA)))'
				,'HRSERV'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.143)(PORT=1521))(CONNECT_DATA=(SID=ORCL)))'
				,'HRSERV2020'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.147)(PORT=1521))(CONNECT_DATA=(SID=XE)))'
				,'SSS-DB'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.24)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=UGOV)))'
				,'SSSUP_PREPROD'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=130.186.81.138)(PORT=5555))(CONNECT_DATA=(SERVICE_NAME=ugov_sssup_preprod_ext.cineca.it)))'
				,'SSSUP_PROD'=>'(DESCRIPTION = (ADDRESS_LIST = (LOAD_BALANCE = ON) (FAILOVER = ON)(ADDRESS=(PROTOCOL=tcp)(HOST=cman01-ext.dbc.cineca.it)(PORT=5555))(ADDRESS=(PROTOCOL=tcp)(HOST=cman02-ext.dbc.cineca.it)(PORT=5555)))(CONNECT_DATA = (SERVICE_NAME = ugov_sssup_prod_ext.cineca.it )))'
				,'IRIS_SSSUP_PREPROD_EXT'=>'(DESCRIPTION=(ADDRESS_LIST=(FAILOVER=on)(LOAD_BALANCE=on)(ADDRESS=(PROTOCOL=TCP)(HOST=cman01-ext.dbc.cineca.it)(PORT=5555))(ADDRESS=(PROTOCOL=TCP)(HOST=cman02-ext.dbc.cineca.it)(PORT=5555)))(CONNECT_DATA=(SERVICE_NAME=IRIS_SSSUP_PREPROD_ext.cineca.it)))'
				,'IRIS_PROD'=>'(DESCRIPTION=(ADDRESS_LIST=(FAILOVER=on)(LOAD_BALANCE=on)(ADDRESS=(PROTOCOL=TCP)(HOST=cman01-ext.dbc.cineca.it)(PORT=5555))(ADDRESS=(PROTOCOL=TCP)(HOST=cman02-ext.dbc.cineca.it)(PORT=5555)))(CONNECT_DATA=(SERVICE_NAME=IRIS_SSSUP_PROD_ext.cineca.it)))'
				,'ORACLESRV'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.103)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=UGOV)))'
				,'ORAXEWIN10'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.140)(PORT=1521))(CONNECT_DATA=(SID=XE)))'
			); 
			if (substr($c,0,12)!='(DESCRIPTION'){	// stringa di connessione
				if (array_key_exists($c, $a)) {
					$c=$a[$c];		// nome nell'array delle connessioni
				} else {
					return false;		// no stringa di connessione e no in array delle connessioni
				}
			}
			try {
				$r = oci_pconnect($u,$p,$c,'AL32UTF8');	// in utf8
			  $e = oci_error();
				if ($e != '') {
					tolog('Oracle Connection failed: ' . $e['message'] . ' - connection: ' . $c . ' - user: ' . $u);
				}				
				if ($r) {
					error_reporting($err_level);
					return $r;
				}
			} catch (PDOException $e) {
				tolog('Oracle Connection failed: ' . $e->getMessage());
				error_reporting($err_level);
				return false;
			}
			break;
		case 'p':	
			// potgres
			$a = array(	// array di connessioni per Postgress
				'PG SIS'=>'host=sisdb-n.local port=5432 dbname=sis'
				,'PG local'=>'host=localhost port=5432 dbname=albo'
				,'PG idb2'=>'host=idb2.local port=5432 dbname=sssdb'
				,'PG albo toshiba'=>'host=10.1.106.194 port=5432 dbname=albo'
				// ,'PG albo dotnetu'=>'host=192.168.64.107 port=5432 dbname=albo'
				,'PG albo dotnetu'=>'host=localhost port=5432 dbname=albo'
				,'PG biblio'=>'host=192.168.64.7 port=5432 dbname=sssdb'
			);	
			if (substr($c,0,5)!='host='){	// stringa di connessione
				if (array_key_exists($c, $a)) {
					$c=$a[$c];		// nome nell'array delle connessioni
				} else {
					return false;		// no stringa di connessione e no in array delle connessioni
				}
			}
			try {
				$r = pg_connect($c." user=$u"." password=$p");	// pg_connect("host=sheep port=5432 dbname=mary user=lamb password=foo");
				return $r;
			} catch (PDOException $e) {
				tolog('Postgres Connection failed: ' . $e->getMessage());
				return false;
			}
			break;
		case 'm':
			// MySQL
			$a = array(	// array di connessioni per MySQL
				'ictservice'=>array('h'=>'ictservice.local','db'=>'covid')	// covid cMo382P6fWiFP2s5
				,'prenotazione aule'=>array('h'=>'crbs.local','db'=>'booked')	// booked n1njaG02020
				,'minisiti'=>array('h'=>'192.168.64.209','db'=>'capitalisegenetics')	// capitalisegenetics c4p1t4l1s3g3n3t1cs
				,'sss_import'=>array('h'=>'localhost','db'=>'sss_import')
				,'idb2_mail'=>array('h'=>'idb2.local','db'=>'mail')
			);	
			if (gettype($c)!='object' && gettype($c)!='array'){	// "boolean" "integer" "double" "string" "array" "object" "resource" "NULL" "unknown type"
				if (array_key_exists($c, $a)) {
					$c=$a[$c];		// nome nell'array delle connessioni
				} else {
					return false;		// no stringa di connessione e no in array delle connessioni
				}
			}
			try {
				$r = mysqli_connect($c['h'], $u, $p);
				// $r = new mysqli($c['h'], $u, $p, $c['db']);	// mysqli_connect(host,username,password,dbname,port,socket);
				mysqli_select_db($r,$c['db']); //or die('Could not select database');
				return $r;
			} catch (PDOException $e) {
				$myer=mysqli_error();
				tolog('MySQL Connection failed: ' . $e->getMessage(). ' ' . $myer);
				return false;
			}
			break;
		case 'l':
			// LDAP o AD	ldap_connect ([ string $host = NULL [, int $port = 389 ]] )
			$a = array(	// array di connessioni per LDAP / AD
				'LDAP'=>array('u'=>'cn=manager,o=sss,c=it','p'=>'fugasse','s'=>'192.168.64.11','porta'=>389,'t'=>'ou=Users,o=sss,c=it')
				,'AD'=>array('u'=>'Administrator','p'=>'aDuNo2016tt','s'=>'192.168.64.81','porta'=>389,'t'=>'ou=OpenLdap,dc=sssapisa,dc=it')
			);
			if (gettype($c)!='object' && gettype($c)!='array'){	// "boolean" "integer" "double" "string" "array" "object" "resource" "NULL" "unknown type"
				if (array_key_exists($c, $a)) {
					$c=$a[$c];		// nome nell'array delle connessioni
				} else {
					return false;		// no stringa di connessione e no in array delle connessioni
				}
			}
			try {
				$r = ldap_connect($c['s'],$c['porta']);	//  or die("Could not connect to LDAP server.");
				if ($r) {
					ldap_set_option($r, LDAP_OPT_PROTOCOL_VERSION, 3);
					ldap_set_option($r, LDAP_OPT_REFERRALS, 0);
					return $r;
				// ] else {	
				//	die('Cannot Connect to LDAP server');
				};
			} catch (PDOException $e) {
				if ($devel){log_on_error('LDAP Connection failed: ' . $e->getMessage());} else {tolog('LDAP Connection failed: ' . $e->getMessage());}
				return false;
			}
			break;
	};
}
function load_dbx($conn,$sql,$dbt='o',$tolog=true,$fetch='o'){
	$a=load_db($conn,$sql,$dbt='o',$tolog,$fetch);
	$b=array('data'=>$a[2],'json'=>json_encode($a[2]),'righe'=>$a[1],'colonne'=>$a[0],'errori'=>$a[3]);
	return $b;
}
function load_db($conn,$sql,$dbt='o',$tolog=true,$fetch='o'){		// dbt: o=oracle, p=Postgres, m=MySQL
	global $out, $ip, $logf, $develerr;

	$_SESSION['last_sql']=$sql;
	if (empty($fetch)){$fetch=$dbt;}
//	error_reporting(0);
	$nc=0;	// colonne
	$nr=0;	// righe
	$result='';	
	if ($tolog==9) {tolog('load_db - sql: '.$sql);}
	$ret=1;
	$noret=['insert','delete','update','truncate','drop'];
	$a=explode(" ",$sql);
	for ($i = 0; $i < count($a); $i++) {
		if (in_array(strtolower($a[$i]), $noret)){$ret=0;}
	}
	$e=false;
	$ee='';

/*	
	attuale in dotneu - PHP Version 7.4.3

	try {
		 // Code that may throw an Exception or Error.
	} catch (Throwable $t) {
		 // Executed only in PHP 7, will not match in PHP 5
	} catch (Exception $e) {
		 // Executed only in PHP 5, will not be reached in PHP 7
	}

	Throwable
		Error
			ArithmeticError
				DivisionByZeroError
			AssertionError
			CompileError
				ParseError
			TypeError
				ArgumentCountError
			ValueError
			UnhandledMatchError
			FiberError
		Exception
			...

	interface Throwable extends Stringable {
		// Methods 
			public getMessage(): string
			public getCode(): int
			public getFile(): string
			public getLine(): int
			public getTrace(): array
			public getTraceAsString(): string
			public getPrevious(): ?Throwable
			abstract public __toString(): string
		// Inherited methods
			public Stringable::__toString(): string
	}

	// getTrace()
	Name			Type			Description
	function	string		The current function name. See also __FUNCTION__.
	line			int				The current line number. See also __LINE__.
	file			string		The current file name. See also __FILE__.
	class			string		The current class name. See also __CLASS__
	object		object		The current object.
	type			string		The current call type. If a method call, "->" is returned. If a static method call, "::" is returned. If a function call, nothing is returned.
	args			array			If inside a function, this lists the functions arguments. If inside an included file, this lists the included file name(s).


	error_get_last();
	Array (
		[type] => 8
		[message] => Undefined variable: a
		[file] => C:\WWW\index.php
		[line] => 2
	)

*/				

	switch ($dbt) {
		case 'o':	
			if ($conn) {
				try{
					$parsed = oci_parse($conn, $sql);		// https://www.php.net/manual/en/function.oci-parse.php
					$e = oci_error($conn);  						// https://www.php.net/manual/en/function.oci-error.php
					$ee=print_r(error_get_last(),true).($e?"\n".print_r($e,true):'');
					if ($ee!=''){tolog('OCI parse error: '.$sql.' - oci_error: '.$ee);$ee='';error_clear_last();}
				} catch(Throwable $e) {
					// $trace = $e->getTrace();
					// $m = $e->getMessage().' in '.$e->getFile().' on line '.$e->getLine().' called from '.$trace[0]['file'].' on line '.$trace[0]['line'];
					$m=print_r($e,true);
					tolog('OCI parse error: '.$sql."\n".'Throwable: '.$m);
					return;
				}
				if (!$e) {
					try{
						$r = oci_execute($parsed); 				// https://www.php.net/manual/en/function.oci-execute.php
						$e = oci_error($conn);  						// https://www.php.net/manual/en/function.oci-error.php
						$ee=print_r(error_get_last(),true).($e?"\n".print_r($e,true):'');
						if ($ee!=''){tolog('OCI execute error: '.$sql.' - oci_error: '.$ee);error_clear_last();}
					} catch(Throwable $e) {
						$m=print_r($e,true);
						tolog('OCI execute error: '.$sql."\n".' - Throwable: '.$m);
						return;
					}
				}		
				if (!$e){
					if ($ret){
						try {
							if ($fetch=='o'){
								$nr = oci_fetch_all($parsed, $result);
								$e = oci_error($conn);  						// https://www.php.net/manual/en/function.oci-error.php
							}
							if ($fetch=='m'){
								$nr = oci_fetch_all($parsed, $result, null, null, OCI_FETCHSTATEMENT_BY_ROW);
								$e = oci_error($conn);  						// https://www.php.net/manual/en/function.oci-error.php
							} // come mysql
							$ee=print_r(error_get_last(),true).($e?"\n".print_r($e,true):'');
							if ($ee!=''){tolog('OCI fetch error: '.$sql.' - oci_error: '.$ee);$ee='';error_clear_last();}
							$nc = oci_num_fields($parsed);					// https://www.php.net/manual/en/function.oci-num-fields.php numero delle colonne
							$e = oci_error($conn);  						// https://www.php.net/manual/en/function.oci-error.php
							$ee=print_r(error_get_last(),true).($e?"\n".print_r($e,true):'');
							if ($ee!=''){tolog('OCI num_field error: '.$sql.' - oci_error: '.$ee);$ee='';error_clear_last();}
							if($tolog) {tolog('righe: '.$nr.' - colonne: '.$nc.' - SQL ==> '.$sql);}
						} catch(Throwable $e) {
							$m=print_r($e,true);
							tolog('ERRORE: '.$sql.' - Throwable: '.$m);
							return;
						}
					} else {
						try {
							$nr = oci_num_rows ($parsed);							// righe affette da insert, update, delete 
							$e = oci_error($conn);  						// https://www.php.net/manual/en/function.oci-error.php
							$ee=print_r(error_get_last(),true).($e?"\n".print_r($e,true):'');
							if ($ee!=''){tolog('OCI num_rows error: '.$sql.' - oci_error: '.$ee);$ee='';error_clear_last();}
							if($tolog) {tolog('righe: '.$nr.' - SQL ==> '.$sql);}
						} catch(Throwable $e) {
							$m=print_r($e,true);
							tolog('ERRORE: '.$sql.' - Throwable: '.$m);
							return;
						}
					}
				}
			}
			break;
		case 'p':	
			try {
				$parsed = pg_query($conn, utf8_encode($sql));
				if (!$parsed) {
					$e =  pg_last_error($conn);
					$ee=print_r(error_get_last(),true).($e?"\n".print_r($e,true):'');
					if ($ee!=''){tolog('POSTGRES ERRORE: '.$sql.' - pg_query: '.$ee);$ee='';error_clear_last();}
				} else {	
					if ($ret){
						$result = pg_fetch_all($parsed);
						if (!$result) {
							$e = pg_last_error($conn);  // For oci_parse errors pass the connection handle
							$ee=print_r(error_get_last(),true).($e?"\n".print_r($e,true):'');
							if ($ee!=''){tolog('POSTGRES ERRORE: '.$sql.' - pg_fetch_all: '.$ee);$ee='';error_clear_last();}
						} else {
							try {
								$nc = pg_num_fields($parsed);	// numero delle colonne (fields)
								$nr = pg_num_rows($parsed);
							} catch (Throwable $e) {
								$m=print_r($e,true);
								tolog('ERRORE: '.$sql.' - Throwable: '.$m);
								return;
							}
						}
					}
				}
			} catch (Throwable $e) {
				$m=print_r($e,true);
				tolog('ERRORE: '.$sql.' - Throwable: '.$m);
				return;
			}
			break;
		case 'm':	
			// $conn = mysqli_connect('ictservice.local', 'covid', 'cMo382P6fWiFP2s5') 
			// 	or die('Could not connect: ' . mysqli_error());
			// mysqli_select_db($conn,"covid") or die('Could not select database');
			// $sql="select * from `covid`.`asso_covid_2` where toc=".$_REQUEST['token']." ;";
			// if ($devel){tolog('SQL = '.$sql);}
			$result=mysqli_query($conn,$sql);
			$nr=mysqli_num_rows($result);
			$nc=mysqli_num_fields($result);
			$e=mysqli_error();		
			break;
	};
//	error_reporting($develerr);
	if ($ret){
		return array($nc,$nr,$result,$e);	// nc=numero colonne, nr=numero righe, result=array dati, errori
	}
}
function ocitocsv($conn, $sql) {
	$parsed = oci_parse($conn, $sql);
	oci_execute($parsed);
	$ncols = oci_num_fields($parsed);
	$nrows = oci_fetch_all($parsed, $results);
	$out='';
	if ($nrows > 0) {
		$out='';
		for ($i = 1; $i <= $ncols; $i++) {
		    $c_name  = oci_field_name($parsed, $i);
		    $c_type  = oci_field_type($parsed, $i);
		    $c_size  = oci_field_size($parsed, $i);
		    if ($i > 1) {$out .= ';';};
			$out .= '"'.$c_name.'"';
		}
	  $out .= " \n";
		for ($j = 0; $j < $nrows; $j++) {
			for ($i = 1; $i <= $ncols; $i++) {
			    $c_name = oci_field_name($parsed, $i);
			    $c_type = oci_field_type($parsed, $i);
			    $c_size = oci_field_size($parsed, $i);
			    if ($i > 1) {$out .= ';';};
				$item=$results[$c_name][$j];
				switch ($c_type) {
					case 'DATE':
						$out .= '"'.$item.'"';
						break;
					case 'VARCHAR2':
						$out .= '"'.$item.'"';
						break;
					case 'NUMBER':
						if ($item==0) {$item='0';};	// ???
						$out .= $item;
						break;
					default:
						$out .= '"'.$item.'"';
				}
			}
		  $out .= " \n";
		}
	};
	return $out;
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
function log_on_error($msg){
	global $logf, $func;
	if ($msg != ''){tolog($msg);}
	// file_put_contents($logf,"--- DUMP DOPO ERRORE --- \r\n",FILE_APPEND);
	file_put_contents($logf,"--- SERVER \r\n",FILE_APPEND);
	foreach ($_SERVER as $key => $value) {
		if (is_array($value)){$v=json_encode($value);} else {$v=$value;}
		file_put_contents($logf,"    $key -> $v\r\n",FILE_APPEND);
	};	
	file_put_contents($logf,"--- SESSION \r\n",FILE_APPEND);
	foreach ($_SESSION as $key => $value) {
		if (is_array($value)){$v=json_encode($value);} else {$v=$value;}
		file_put_contents($logf,"    $key -> $v\r\n",FILE_APPEND);
	};	
	file_put_contents($logf,"--- REQUEST \r\n",FILE_APPEND);
	foreach ($_REQUEST as $key => $value) {	// _REQUEST contiene _GET o _POST
		if (is_array($value)){$v=json_encode($value);} else {$v=$value;}
		file_put_contents($logf,"    $key -> $v\r\n",FILE_APPEND);
	};

}
function tolog($messaggio){
	global $ip,$logf,$app; 
	if (empty($app)){$app='_generico';}
	if (empty($logf)){$logf=checklog($app);}
	if (empty($ip)){$ip=getIpAddress();}
	$uid='######';
	if (!empty($_SESSION['uid_login'])){$uid=$_SESSION['uid_login'];}
	if (!empty($_SESSION[$app]['uid_login'])){$uid=$_SESSION[$app]['uid_login'];}
	$napp=getcwd() . ' (' . __FILE__ . ')';
	file_put_contents($logf, $napp.' - ' . date("Ymd His").' - ip: '.$ip.' - uid: '.$uid.' - '.$messaggio."\r\n", FILE_APPEND | LOCK_EX);
}	
function tologerr($messaggio){
	global $app; 
	if (empty($app)){$app='_generico';}
	$logf='../log/errori_'.$app.'_'.date("Ym").'.log';
	if (!file_exists($logf)) {	// creo il file di log se non esiste
		$myfile = fopen($logf,"w") or die("Unable to open file!");
		fwrite($myfile,"");
		fclose($myfile);
		chmod($logf, 0777);
		tolog("creato il file di log per gli errori: $logf");
	}
	$ip=getIpAddress();
	$uid='######';
	if (!empty($_SESSION['uid_login'])){$uid=$_SESSION['uid_login'];}
	if (!empty($_SESSION[$app]['uid_login'])){$uid=$_SESSION[$app]['uid_login'];}
	$napp=getcwd();
	file_put_contents($logf, $napp.' - ' . date("Ymd His").' - ip: '.$ip.' - uid: '.$uid."\n".$messaggio."\n", FILE_APPEND | LOCK_EX);
}
function alboError($errno, $errstr, $errfile, $errline) {
	$msge="\n  Error: [$errno] $errstr\n  source: $errfile\n  line: $errline\n";
	// if (empty($_SESSION['ab_msg_err'])){$_SESSION['ab_msg_err']=$m;}
	$napp=__FILE__; // getcwd().$_SERVER['PHP_SELF'];
	// $errfile contiene il path completo dello script originario dell'errore
	// $anf=explode('.',end(explode('/',$errfile)));

	$nf=strtoupper($errfile);
	$anf=explode('/',$nf);
	$eanf=end($anf);
	$anf=explode('.',$eanf);
	$app=$anf[0];

	$ip=getIpAddress();
	$uid='######';
	if (!empty($_SESSION['uid_login'])){$uid=$_SESSION['uid_login'];}
	if (!empty($_SESSION[$app]['uid_login'])){$uid=$_SESSION[$app]['uid_login'];}
	if (!empty($_SESSION[$GLOBALS['app']]['uid_login'])){$uid=$_SESSION[$GLOBALS['app']]['uid_login'];}
	if (!empty($_SESSION['last_sql'])){$sql=$_SESSION['last_sql'];} else {$sql='';}
	$msgs="sql: $sql\n";
	$key=microtime();
	$msgd="data: ".date("Ymd His")."\n";
	$msgk="key: $key\n";
	if (!empty($_REQUEST)){$msgr="request: ".json_encode($_REQUEST)."\n";} else {$msgr="";}
	$msgm="memory: ".memory_get_usage()."\n";

	$msg="";
	$msg.="php: $napp - $errfile\n";
	$msg.="ip: $ip\n";
	$msg.="uid: $uid\n";
/*
esempio:
trace: Array
(
    [0] => Array
        (
            [function] => alboError
            [args] => Array
                (
                    [0] => 2
                    [1] => oci_execute(): ORA-00942: tabella o vista inesistente
                    [2] => /opt/html/rac/_comuni/comune.php
                    [3] => 386
                    [4] => Array
                        (
                            [conn] => Resource id #5
                            [sql] => select count(*) n from C##SSS_DIDATTICA.strutture
                            [dbt] => o
                            [tolog] => 1
                            [fetch] => o
                            [out] =>
                            [ip] =>
                            [logf] => /var/www/log/DIDATTICA_202301.log
                            [develerr] =>
                            [nc] => 0
                            [nr] => 0
                            [result] =>
                            [ret] => 1
                            [noret] => Array
                                (
                                    [0] => insert
                                    [1] => delete
                                    [2] => update
                                    [3] => truncate
                                    [4] => drop
                                )

                            [a] => Array
                                (
                                    [0] => select
                                    [1] => count(*)
                                    [2] => n
                                    [3] => from
                                    [4] => C##SSS_DIDATTICA.strutture
                                )

                            [i] => 5
                            [e] =>
                            [ee] =>
                            [parsed] => Resource id #65
                        )

                )

        )

    [1] => Array
        (
            [file] => /opt/html/rac/_comuni/comune.php
            [line] => 386
            [function] => oci_execute
            [args] => Array
                (
                    [0] => Resource id #65
                )

        )

    [2] => Array
        (
            [file] => /opt/html/batch/ricrea_tabelle_didattica.php
            [line] => 63
            [function] => load_db
            [args] => Array
                (
                    [0] => Resource id #5
                    [1] => select count(*) n from C##SSS_DIDATTICA.strutture
                    [2] => o
                )

        )

)
*/	
	// $msg.="trace: ".print_r(debug_backtrace(),true)."\n";
    $dbgTrace = debug_backtrace();
	$msg.=getDebugBacktrace($dbgTrace,"\n")."\n";
	$to="alberto.bongiorni@santannapisa.it";
	$headers="Subject:$uid php error in $errfile\nFrom: dotnetu@santannapisa.it";
	if (empty($_SESSION['ab_msg_sql']) or empty($_SESSION['ab_msg_error'])){
		$_SESSION['ab_msg_sql']='';
		$_SESSION['ab_msg_error']='';
	} 
	if ($msgs != $_SESSION['ab_msg_sql'] and $msge != $_SESSION['ab_msg_error']){
		error_log($msgd.$msgk.$msg.$msgr.$msgs.$msge,1,$to,$headers);	// invia mail di errore
		$_SESSION['ab_msg_sql']=$msgs;
		$_SESSION['ab_msg_error']=$msge;
	}
	tologerr($msgd.$msgk.$msg.$msgr.$msgs.$msge);
	tolog($msge."last_sql: $sql\n");
}
function getDebugBacktrace($dbgTrace, $NL = "\n") {
    if (empty($dbgTrace) or $dbgTrace == "\n"){
		$dbgTrace = debug_backtrace();
		$NL = "\n";
	}
    $dbgMsg = ''; // $NL."Debug backtrace begin".$NL;
    foreach($dbgTrace as $dbgIndex => $dbgInfo) {
        $dbgMsg .= "\t at ".$dbgIndex." ".$NL;
		if (!empty($dbgInfo['file'])){$dbgMsg .= $dbgInfo['file'].$NL;}
		if (!empty($dbgInfo['line'])){$dbgMsg .= " (line ".$dbgInfo['line'].")".$NL;}
		if (!empty($dbgInfo['function'])){
			$dbgMsg .= " (function ".$dbgInfo['function'].") ".$NL;
			if ($dbgInfo['function'] == 'load_db' and !empty($dbgInfo['args'])){
				if (!empty($dbgInfo['args'][1])){
					$dbgMsg .= "(sql ".$dbgInfo['args'][1].")".$NL;
				}
			}
		}
		if (!empty($dbgInfo['args'])){
			$args='';
			foreach($dbgInfo['args'] as $argsk => $argsi){
				if (is_array($argsi)){
					// if (!empty($argsi['sql'])){
						// if ($args != ''){$args.=',';}
						// $args.='(sql: '.$argsi['sql'].')';
					// }
				} else {
					if ($args != ''){$args.=',';}
					$args.='('.$argsi.')';
				}
			}
			$dbgMsg .= "(args ".$args.")".$NL;
		}
	}
    // $dbgMsg .= "Debug backtrace end".$NL;
    return $dbgMsg;
}
function cidr2range($ipv4){
	if ($ip=strpos($ipv4,'/')){
		$n_ip=(1<<(32-substr($ipv4,1+$ip)))-1;   
		$ip_dec=ip2long(substr($ipv4,0,$ip)); 
	}	else {
		$n_ip=0;
		$ip_dec=ip2long($ipv4);             
	}
	$ip_min=$ip_dec&~$n_ip;
	$ip_max=$ip_min+$n_ip;
	#Array(2) of Decimal Values Range
	return [$ip_min,$ip_max];
	#Array(2) of Ipv4 Human Readable Range
	return [long2ip($ip_min),long2ip($ip_max)];
	#Array(2) of Ipv4 and Subnet Range
	return [long2ip($ip_min),long2ip(~$n_ip)];
	#Array(2) of Ipv4 and Wildcard Bits
	return [long2ip($ip_min),long2ip($n_ip)];
	#Integer Number of Ipv4 in Range
	return ++$n_ip;
}
function datediff($tipo, $partenza, $fine){
	// $partenza e $fine = data stringa in formato dd/mm/aaaa
	// if ($partenza instanceof DateTime){$partenza=date($partenza, 'd/m/T');}
	// if ($fine instanceof DateTime){$fine=date($fine, 'd/m/T');}
	switch ($tipo){
		case "A" : $tipo = 365;						break;	// anni
		case "M" : $tipo = (365 / 12);		break;  // mesi
		case "S" : $tipo = (365 / 52);		break;  // settimane
		case "G" : $tipo = 1;							break;  // giorni
	}
	$arr_partenza = explode("/", $partenza);
	$partenza_gg = $arr_partenza[0];
	$partenza_mm = $arr_partenza[1];
	$partenza_aa = $arr_partenza[2];
	$arr_fine = explode("/", $fine);
	$fine_gg = $arr_fine[0];
	$fine_mm = $arr_fine[1];
	$fine_aa = $arr_fine[2];
	$date_diff = mktime(12, 0, 0, $fine_mm, $fine_gg, $fine_aa) - mktime(12, 0, 0, $partenza_mm, $partenza_gg, $partenza_aa);
	$date_diff  = floor(($date_diff / 60 / 60 / 24) / $tipo);
	return $date_diff;
}
function ControllaCF($cf) {
	if( $cf === '' )  return '';
	if( strlen($cf) != 16 ) {
		return "La lunghezza del codice fiscale non &egrave; corretta:
		<br>il codice fiscale dovrebbe essere lungo esattamente 16 caratteri.";
	}
	$cf = strtoupper($cf);
	if( preg_match("/^[A-Z0-9]+\$/D", $cf) != 1 ){
		return "Il codice fiscale contiene dei caratteri non validi:
		<br>i soli caratteri validi sono le lettere e le cifre.";
	}
	$s = 0;
	for( $i = 1; $i <= 13; $i += 2 ){
		$c = $cf[$i];
		if( strcmp($c, "0") >= 0 and strcmp($c, "9") <= 0 ){
			$s += ord($c) - ord('0');
		} else {
			$s += ord($c) - ord('A');
		}
	}
	for( $i = 0; $i <= 14; $i += 2 ){
		$c = $cf[$i];
		switch( $c ){
			case '0':  $s += 1;  break;
			case '1':  $s += 0;  break;
			case '2':  $s += 5;  break;
			case '3':  $s += 7;  break;
			case '4':  $s += 9;  break;
			case '5':  $s += 13;  break;
			case '6':  $s += 15;  break;
			case '7':  $s += 17;  break;
			case '8':  $s += 19;  break;
			case '9':  $s += 21;  break;
			case 'A':  $s += 1;  break;
			case 'B':  $s += 0;  break;
			case 'C':  $s += 5;  break;
			case 'D':  $s += 7;  break;
			case 'E':  $s += 9;  break;
			case 'F':  $s += 13;  break;
			case 'G':  $s += 15;  break;
			case 'H':  $s += 17;  break;
			case 'I':  $s += 19;  break;
			case 'J':  $s += 21;  break;
			case 'K':  $s += 2;  break;
			case 'L':  $s += 4;  break;
			case 'M':  $s += 18;  break;
			case 'N':  $s += 20;  break;
			case 'O':  $s += 11;  break;
			case 'P':  $s += 3;  break;
			case 'Q':  $s += 6;  break;
			case 'R':  $s += 8;  break;
			case 'S':  $s += 12;  break;
			case 'T':  $s += 14;  break;
			case 'U':  $s += 16;  break;
			case 'V':  $s += 10;  break;
			case 'W':  $s += 22;  break;
			case 'X':  $s += 25;  break;
			case 'Y':  $s += 24;  break;
			case 'Z':  $s += 23;  break;
			/*. missing_default: .*/
		}
	}
	if( chr($s%26 + ord('A')) != $cf[15] ) {
		return "Il codice fiscale non &egrave; corretto:
		<br>il codice di controllo non corrisponde.";
	}
	return "";
}
class CheckCF {
	/*
		uso: 
		$chk = new CheckCF();
		if ($chk->isFormallyCorrect('BNGLRT57T20G702B')) {
				print('Codice Fiscale formally correct');
				printf('Birth Day: %s',     $chk->getDayBirth());
				printf('Birth Month: %s',   $chk->getMonthBirth());
				printf('Birth Year: %s',    $chk->getYearBirth());
				printf('Birth Country: %s', $chk->getCountryBirth());
				printf('Sex: %s',           $chk->getSex());
		} else {
				print('Codice Fiscale wrong');
		}		
	*/
	const REGEX_CODICEFISCALE = '/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z][0-9]{3}[a-z]$/i';		// fiscal's code regex
	const CHR_WOMEN = 'F';	// women char
	const CHR_MALE = 'M';		// male char
	private $isValid = false;
	private $sex = null;
	private $countryBirth = null;
	private $dayBirth = null;
	private $monthBirth = null;
	private $yearBirth = null;
	private $error = null;
	private $listDecOmocodia = array('A' => '!', 'B' => '!', 'C' => '!', 'D' => '!', 'E' => '!', 'F' => '!', 'G' => '!', 'H' => '!', 'I' => '!', 'J' => '!', 'K' => '!', 'L' => '0', 'M' => '1', 'N' => '2', 'O' => '!', 'P' => '3', 'Q' => '4', 'R' => '5', 'S' => '6', 'T' => '7', 'U' => '8', 'V' => '9', 'W' => '!', 'X' => '!', 'Y' => '!', 'Z' => '!',);
	private $listSostOmocodia = array(6, 7, 9, 10, 12, 13, 14);
	private $listEvenChar = array('0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, 'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23, 'Y' => 24, 'Z' => 25);
	private $listOddChar = array('0' => 1, '1' => 0, '2' => 5, '3' => 7, '4' => 9, '5' => 13, '6' => 15, '7' => 17, '8' => 19, '9' => 21, 'A' => 1, 'B' => 0, 'C' => 5, 'D' => 7, 'E' => 9, 'F' => 13, 'G' => 15, 'H' => 17, 'I' => 19, 'J' => 21, 'K' => 2, 'L' => 4, 'M' => 18, 'N' => 20, 'O' => 11, 'P' => 3, 'Q' => 6, 'R' => 8, 'S' => 12, 'T' => 14, 'U' => 16, 'V' => 10, 'W' => 22, 'X' => 25, 'Y' => 24, 'Z' => 23);
	private $listCtrlCode = array(0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D', 4 => 'E', 5 => 'F', 6 => 'G', 7 => 'H', 8 => 'I', 9 => 'J', 10 => 'K', 11 => 'L', 12 => 'M', 13 => 'N', 14 => 'O', 15 => 'P', 16 => 'Q', 17 => 'R', 18 => 'S', 19 => 'T', 20 => 'U', 21 => 'V', 22 => 'W', 23 => 'X', 24 => 'Y', 25 => 'Z');
	private $listDecMonth = array('A' => '01', 'B' => '02', 'C' => '03', 'D' => '04', 'E' => '05', 'H' => '06', 'L' => '07', 'M' => '08', 'P' => '09', 'R' => '10', 'S' => '11', 'T' => '12');
	// private $listError = array(0 => 'Empty code', 1 => 'Len error', 2 => 'Code with wrong char', 3 => 'Code with wrong char in omocodia', 4 => 'Wrong code');
	private $listError = array(0 => 'Nessun CF', 1 => 'CF con lunghezza diversa da 16 crt', 2 => 'CF con caratteri non ammessi', 3 => 'CF con caratteri non ammessi in omocodia', 4 => 'CF con codice di controllo errato');
	public function getIsValid() {			return $this->isValid;			}
	public function getError() {				return $this->error;				}
	public function getSex() {					return $this->sex;					}
	public function getCountryBirth() {	return $this->countryBirth;	}
	public function getYearBirth() {		return $this->yearBirth;		}
	public function getMonthBirth() {		return $this->monthBirth;		}
	public function getDayBirth() {			return $this->dayBirth;			}
	public function isFormallyCorrect($codiceFiscale) {
		$this->resetProperties();
		try {
			if (empty($codiceFiscale)) {	$this->raiseException(0);	}																		// check empty
			if (strlen($codiceFiscale) !== 16) { $this->raiseException(1); }														// Vcheck len
			if (!preg_match(self::REGEX_CODICEFISCALE, $codiceFiscale)) {$this->raiseException(2);}			// Check regex
			$codiceFiscale = strtoupper($codiceFiscale);
			$cFCharList = str_split($codiceFiscale);
			for ($i = 0; $i < count($this->listSostOmocodia); $i++) {																		// check omocodia
				if (!is_numeric($cFCharList[$this->listSostOmocodia[$i]])) {
					if ($this->listDecOmocodia[$cFCharList[$this->listSostOmocodia[$i]]] === '!') {$this->raiseException(3);}
				}
			}
			$pari = 0;
			$dispari = $this->listOddChar[$cFCharList[14]];
			for ($i = 0; $i < 13; $i += 2) {																							// loop first 14 char, step 2
				$dispari = $dispari + $this->listOddChar[$cFCharList[$i]];
				$pari = $pari + $this->listEvenChar[$cFCharList[$i + 1]];
			}
			// verify first 15 char with checksum char (char 16)
			if (!($this->listCtrlCode[($pari + $dispari) % 26] === $cFCharList[15])) {$this->raiseException(4);}
			for ($i = 0; $i < count($this->listSostOmocodia); $i++) {											// replace "omocodie"
				if (!is_numeric($cFCharList[$this->listSostOmocodia[$i]])) {
					$CFCharList[$this->listSostOmocodia[$i]] = $this->listDecOmocodia[$cFCharList[$this->listSostOmocodia[$i]]];
				}
			}
			$codiceFiscaleAdattato = implode($cFCharList);
			// get fiscal code data
			$this->sex = ((int)(substr($codiceFiscaleAdattato, 9, 2) > 40) ? self::CHR_WOMEN : self::CHR_MALE);
			$this->countryBirth = substr($codiceFiscaleAdattato, 11, 4);
			$this->yearBirth = substr($codiceFiscaleAdattato, 6, 2);
			$this->dayBirth = substr($codiceFiscaleAdattato, 9, 2);
			$this->monthBirth = $this->listDecMonth[substr($codiceFiscaleAdattato, 8, 1)];
			// get day birth if sex is women
			if ($this->sex == self::CHR_WOMEN) {
				$this->dayBirth = $this->dayBirth - 40;
				if (strlen($this->dayBirth) === 1) {$this->dayBirth = '0' . $this->dayBirth;}
			}
			// End verify
			$this->isValid = true;
			$this->error = null;
		} catch (\Exception $e) {
			$this->error = $e->getMessage();
			$this->isValid = false;
		}
		return $this->isValid;
	}
	private function resetProperties() {
			$this->isValid = false;
			$this->sex = null;
			$this->countryBirth = null;
			$this->dayBirth = null;
			$this->monthBirth = null;
			$this->yearBirth = null;
			$this->error = null;
	}
	private function raiseException($errorNum) {
			$errMessage = isset($this->listError[$errorNum]) ? $this->listError[$errorNum] : 'Unknown Exception';
			throw new \Exception($errMessage, $errorNum);
	}
}
class CalcolaCF {
	/*
		uso: 
		$calc = new CalcolaCF();
		$cf = $calc->calcola('Alberto', 'Bongiorni', 'M', new \DateTime('1957-12-20'), 'G702');		
	*/
	private $mesi = ['A', 'B', 'C', 'D', 'E', 'H', 'L', 'M', 'P', 'R', 'S', 'T'];
	private $vocali = ['A', 'E', 'I', 'O', 'U'];
	private $consonanti = ['B', 'C', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'V', 'W', 'X', 'Y', 'Z'];
	private $numeri = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
	private $alfabeto = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
	private $matriceCodiceControllo = ["01" => 1, "00" => 0, "11" => 0, "10" => 1, "21" => 5, "20" => 2, "31" => 7, "30" => 3, "41" => 9, "40" => 4, "51" => 13, "50" => 5, "61" => 15, "60" => 6, "71" => 17, "70" => 7, "81" => 19, "80" => 8, "91" => 21, "90" => 9, "101" => 1, "100" => 0, "111" => 0, "110" => 1, "121" => 5, "120" => 2, "131" => 7, "130" => 3, "141" => 9, "140" => 4, "151" => 13, "150" => 5, "161" => 15, "160" => 6, "171" => 17, "170" => 7, "181" => 19, "180" => 8, "191" => 21, "190" => 9, "201" => 2, "200" => 10, "211" => 4, "210" => 11, "221" => 18, "220" => 12, "231" => 20, "230" => 13, "241" => 11, "240" => 14, "251" => 3, "250" => 15, "261" => 6, "260" => 16, "271" => 8, "270" => 17, "281" => 12, "280" => 18, "291" => 14, "290" => 19, "301" => 16, "300" => 20, "311" => 10, "310" => 21, "321" => 22, "320" => 22, "331" => 25, "330" => 23, "341" => 24, "340" => 24, "351" => 23, "350" => 25];
	public function calcola($nome, $cognome, $sesso, \DateTime $dataNascita, $codiceComune) {
		$codiceFiscale = '';
		$nome = $this->sanitizeString($nome);
		$cognome = $this->sanitizeString($cognome);
		$sesso = $this->sanitizeString($sesso);
		$giorno = $dataNascita->format('d');
		$mese = $dataNascita->format('n');
		$anno = $dataNascita->format('y');
		// inizia con il calcolo dei primi sei caratteri corrispondenti al nome e cognome
		$codiceFiscale = $this->calcolaCognome($cognome) . $this->calcolaNome($nome);
		// calcola i dati corrispondenti alla data di nascita
		if ($sesso == 'F') {$giorno += 40;}
		$codiceFiscale .= $anno . $this->mesi[$mese - 1] . $giorno;
		// aggiunge il codice del comune
		$codiceFiscale .= $codiceComune;
		// e finalmente calcola il codice controllo
		$codiceControllo = 0;
		$alfanumerico = array_merge($this->numeri, $this->alfabeto);
		for ($i = 0; $i < 15; $i++) {
			$codiceControllo += $this->matriceCodiceControllo[strval(array_search($codiceFiscale[$i], $alfanumerico)) . strval((($i + 1) % 2))];
		}
		$codiceFiscale .= $this->alfabeto[$codiceControllo % 26];
		return $codiceFiscale;
	}
	private function calcolaNome($string) {
		$i = 0;
		$res = '';
		$cons = '';
		while (strlen($cons) < 4 && ($i + 1 <= strlen($string))) {
			if (array_search($string[$i], $this->consonanti) !== false) {$cons .= $string[$i];}
			$i++;
		}
		if (strlen($cons) > 3) {
			$res = $cons[0] . $cons[2] . $cons[3];
			return $res;
		} else {
			$res = $cons;
		}
		// Se non bastano prendo le vocali
		$i = 0;
		while (strlen($res) < 3 && ($i + 1 <= strlen($string))) {
			if (array_search($string[$i], $this->vocali) !== false) {$res .= $string[$i];}
			$i++;
		}
		$res .= "XXX";
		return substr($res, 0, 3);
	}
	private function calcolaCognome($string) {
			$res = '';
			$i = 0;
			while(strlen($res) < 3 && ($i + 1 <= strlen($string))) {
				if (array_search($string[$i], $this->consonanti) !== false) {$res .= $string[$i];}
				$i++;
			}
			// Se non bastano le consonanti, prendo le vocali
			$i = 0;
			while(strlen($res) < 3 && ($i + 1 <= strlen($string))) {
				if (array_search($string[$i], $this->vocali) !== false) {$res .= $string[$i];}
				$i++;
			}
			$res .= "XXX";
			return substr($res, 0, 3);
	}
	private function sanitizeString($string) {
			$string = trim($string);
			$string = strtoupper(iconv('UTF-8', 'ASCII//TRANSLIT', $string));
			$string = str_replace(' ', '', $string);
			return $string;
	}
}
?>
