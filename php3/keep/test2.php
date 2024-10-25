<?php
        $myServer="127.0.0.1:3307";
        $myUser="icape";
        $myPass="superfasterness!";
        $myDB="icape";


//        ob_start();
      try {
          $local_mysql = new PDO  ("mysql:host=$myServer;dbname=$myDB",$myUser,$myPass);
          } catch ( PDOException $e){
		echo "error";
                if ($_local_sql_debug) {
  //                      ob_end_clean();
                        echo "Error connecting to Mysql on $myServer<bR>";
                       // echo $e->getMessage();
   //                     ob_start();
                }
                unset($local_mysql);
          }
 //         ob_end_clean();
?>
