<?

//  Database Information


$myServer = '127.0.0.1';   // Should be localhost for local dbase

$myUser = 'root';

$myPass = 'fast#SQL!';

$myDB = 'sms';


// Database access


    try {
        $connection = new PDO  ("mysql:host=$myServer;dbname=$myDB",$myUser,$myPass)
          or die("Couldn't connect to SQL Server on $myServer");
          } catch ( PDOException $e){
            echo "Error connecting to Mysql on $myServer<bR>";
            echo $e->getMessage();
                exit;
        }  

$mysql_conn=$connection;


// --------------------------------------------------------------------


function close_mysql() {
        global $mysql_conn;
	unset($mysql_conn);
};
?>
