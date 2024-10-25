<?


// *** DO NOT COPY THIS TO ANY DIRECTORY AS IT WILL USE IT IN THAT LOCAL DIR INSTEAD OF LIVE CODE FROM PHP3 DIRECTORY ***

// MIGRATION TO PHP 5  Important notes:
// Add to /usr/local/lib/php.ini:   date.timezone = 'Africa/Johannesburg'

// NB Variable name cannot $thiss
// Insert & in /ops/route.phtml :   function db_parse(&$cursor,$qry) 
/*
replace "\$this->" "\$tttt->" -- *php *phtml
 replace "\$thiss" "\$thisss" -- *php *phtml
 replace "\$tttt->" "\$this->" -- *php *phtml


Deprecated functions:  ereg*() 
We can still use them, just hide the errors , by updating /usr/local/lib/php.ini  (or by putting the ini_set command below)
error_reporting = E_ALL & ~E_NOTICE  &  ~E_DEPRECATED  & ~E_USER_DEPRECATED
*/


//ini_set("error_reporting","error_reporting = E_ALL & ~E_NOTICE  &  ~E_DEPRECATED  & ~E_USER_DEPRECATED");

if (getenv("REMOTE_USER")=="Computicket")
  ini_set("display_errors",'0');
else
 error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);


 if (strstr($_SERVER["HTTP_USER_AGENT"],"Android"))
	$using_a_tablet=true;
 else
	$using_a_tablet=false;


////////////////// VERY IMPORTANT:  //////////////////////
        $REMOTE_USER=$_SERVER['REMOTE_USER'];
	if ($REMOTE_USER=="")
		$REMOTE_USER=$otrusername;
        putenv("REMOTE_USER=$REMOTE_USER");
//////////////////////////////////////////



function ora_rollback($conexion) {
	return	oci_rollback($conexion);
}

function ora_commit($conexion) {
	if (oci_commit($conexion)) 
		return true;
	else {
		
                 $e = oci_error();


                if (!$suppress) {
                        echo "<Br><font color=red><b>ERROR: ";
                        echo $e['message'];
                        echo "</b></font><br>";
                }

//                if ($filelog) {
                        $now=date("d/m/Y H:i:s");
                        $outfile=fopen("/var/log/php/php_errors.log","a+");
                        fputs($outfile,$now." - ".$e['message']." - ".getenv("REMOTE_USER")." - ".$_SERVER["SCRIPT_NAME"]." - COMMIT FAILURE\n");
                        fclose($outfile);
 //               }

                return false;


	}
}


function Ora_Logon($username, $password) 
{ 
	$bits=explode("@",$username);
	$bits2=explode("_",$bits[1]);
	//echo " oci_connect($bits[0],$password,'//$bits2[1]/$bits2[0]');<bR>";
        $con = oci_connect($bits[0],$password,"//$bits2[1]/$bits2[0]"); 
	if (!$con) {
	    $e = oci_error();
		echo "<pre>";
		print_r($e);
//		echo "<Br>Username:$username<br>Password:$password<br>";
//		echo file_get_contents("/u01/oracle/product/12.1.0/icape/network/admin/tnsnames.ora");

	 //   trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
	}

//	echo "C2";
        return $con; 
} 

function Ora_Open($conexion) { 
        $cursor[0]=$conexion; 
        return $cursor; 
} 

function ora_numrows(&$cursor) {
	return oci_num_rows($cursor[1]);	

}

function Ora_Parse(&$cursor, $consulta, $suppress=false,$filelog=true) { 

	global $oracle_quiet;

	if ($oracle_quiet)
		$suppress=true;

/*	if (getenv("REMOTE_ADDR")=="192.168.208.64")
	{
		echo "Parse called with $consulta /";
		print_r($cursor);
		echo "<bR>";
	}
*/


	$cursor[9]=$consulta;
        $cursor[1]=@oci_parse($cursor[0],$consulta); 

//	echo "Result $cursor[1]<bR>";

	if (!$cursor[1]) {
		 $e = oci_error($cursor[0]);
	
                $bt = debug_backtrace();
                $caller = array_shift($bt);

                $file= $caller['file'];
                $line=$caller['line'];
	

		if (!$suppress) {
			echo "<Br><font color=red><b>ERROR: ";
			echo $e['message'];
			echo "</b></font><br>";
		}

		if ($filelog) {
			$now=date("d/m/Y H:i:s");
			$outfile=fopen("/var/log/php/php_errors.log","a+");
			fputs($outfile,$now." - ".$e['message']." - ".getenv("REMOTE_USER")." - ".$_SERVER["SCRIPT_NAME"]." - $file:$line - $consulta\n");
			fclose($outfile);
		}
		
		return false;
	}
//	echo "Parsing $consulta on $cursor[1]<br>";

        return $cursor; 
} 

function Ora_Exec(&$cursor,$suppress=false,$filelog=true) { 

       global $oracle_quiet;

        if ($oracle_quiet)
                $suppress=true;

	if (!$filelog)
                        ini_set("log_errors",'0');


        if (!@oci_execute($cursor[1],OCI_NO_AUTO_COMMIT)) // from php 5.3.2 onwards, use OCI_NO_AUTO_COMMIT, before that, use OCI_DEFAULT
	{
		$e = oci_error($cursor[1]);

                $bt = debug_backtrace();
                $caller = array_shift($bt);

                $file= $caller['file'];
                $line=$caller['line'];

		if (!$suppress) {
			echo "<br><font color=red><b>Error: ";
		        echo $e['message'];
			echo "</b></font><br>";
		}
                $now=date("d/m/Y H:i:s");
		if ($filelog) {
       	 	        $outfile=fopen("/var/log/php/php_errors.log","a+");
	                fputs($outfile,$now." - ".$e['message']." - ".getenv("REMOTE_USER")." - ".$_SERVER["SCRIPT_NAME"]." - $file:$line - EXEC:$cursor[9]\n");
                	fclose($outfile);
		}

		 if (!$filelog)
                        ini_set("log_errors",'1');


		return false;
	}
//	elseif (getenv("REMOTE_ADDR")=="192.168.10.64")
 //                       echo "RETTRUE";


                 if (!$filelog)
                        ini_set("log_errors",'1');


        $cursor[2]=1; 
        return $cursor; 
} 

function ora_commitoff($conn) {
	// do nothing - we are always in this mode...
}

function Ora_Fetch(&$cursor) 
{ 
        if ($cursor[2] == 1) $cursor[2]=0; 

        $data=oci_fetch($cursor[1]); 
	if ($data===false) {

                 $e = oci_error($cursor[1]);

		if ($e===false)
			return false; // just nothing fetched

		$bt = debug_backtrace();
		$caller = array_shift($bt);
		
	    	$file= $caller['file'];
     		$line=$caller['line'];


                if (!$suppress) {
                        echo "<Br><font color=red><b>ERROR: ";
                        echo $e['message'];
                        echo "</b></font><br>";
                }

         //       if ($filelog) {
                        $now=date("d/m/Y H:i:s");
                        $outfile=fopen("/var/log/php/php_errors.log","a+");
                        fputs($outfile,$now." - ".$e['message']." - ".getenv("REMOTE_USER")." - ".$_SERVER["SCRIPT_NAME"]." - $file:$line - FETCH FAILURE: $cursor[9]\n");
                        fclose($outfile);
          //      }

                return false;


	}
	else return $data;
} 

/*
echo "BOTH = ".OCI_BOTH."<br>";
echo "ASSOC = ".OCI_ASSOC."<bR>";
echo "NUM = ".OCI_NUM."<bR>";
echo "NULLS = ".OCI_RETURN_NULLS."<bR>";
*/

define("ORA_FETCHINTO_ASSOC",OCI_ASSOC|OCI_RETURN_NULLS);

function ora_fetch_into(&$cursor,&$data,$flags="")
{
	if ($cursor[2] == 1) $cursor[2]=0;
	if (strstr($flags,"ORA_FETCHINTO_ASSOC"))
		$flags=OCI_ASSOC|OCI_RETURN_NULLS;
	elseif ($flags=="")
	{
		$flags=OCI_NUM|OCI_RETURN_NULLS;
//		echo "going for numbers";
	}

//	echo "Flags: $flags";

	$data= oci_fetch_array($cursor[1],$flags);

	if ($data===false) {
                 $e = oci_error($cursor[1]);

		
                if ($e===false)
                        return false; // just nothing fetched



                $bt = debug_backtrace();
		$caller = array_shift($bt);
                $file= $caller['file'];
                $line=$caller['line'];



                if (!$suppress) {
                        echo "<Br><font color=red><b>ERROR: ";
                        echo $e['message'];
                        echo "</b></font><br>";
                }

             //   if ($filelog) {
                        $now=date("d/m/Y H:i:s");
                        $outfile=fopen("/var/log/php/php_errors.log","a+");
                        fputs($outfile,$now." - ".$e['message']." - ".getenv("REMOTE_USER")." - ".$_SERVER["SCRIPT_NAME"]." - $file:$line - FETCHINTO FAILURE: $cursor[9]\n\n");
                        fclose($outfile);
             //   }

                return false;


	} 
	else return $data;

}

function Ora_GetColumn(&$cursor, $indice) 
{ 
        if ($cursor[2] == 1) { 
                Ora_Fetch($cursor); 
                $cursor[2]=0; 
        } 
        $valor = oci_result($cursor[1],$indice+1); 
	//echo "Fetching $indice + 1 on $cursor[1] = $valor<br>";
        return $valor; 
} 

function getdata(&$cursor, $indice)
{
        if ($cursor[2] == 1) {
                Ora_Fetch($cursor);
                $cursor[2]=0;
        }
        $valor = @oci_result($cursor[1],$indice+1);
        //echo "Fetching $indice + 1 on $cursor[1] = $valor<br>";
        return chop($valor);
}


function Ora_Close(&$cursor) 
{ 
        unset($cursor[1]); 
} 

function Ora_Logoff($conexion) { 
	oci_commit($conexion);
	return oci_close($conexion);

} 


require_once("mysql_old.php");

?>
