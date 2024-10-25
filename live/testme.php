<?

//$bin="3P1";
$bin="ZE10";

           for ($a=strlen($bin)-1;isset($bin[$a]) && ord($bin[$a])>=48 && ord($bin[$a])<=57;$a--)
                                $binletter=substr($bin,0,$a);
                                $binnumber=substr($bin,$a+1,999);


echo "Letter $binletter Number $binnumber<Br>";
?>
