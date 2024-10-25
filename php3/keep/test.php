<?php


//putenv("NLS_LANG=AMERICAN_AMERICA.AL32UTF8");

putenv("NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'");

putenv("ORA_SDTZ=UTC");

include ("oracle.inc");
//open_oracle();

$dbcon = OCI_connect("ADMIN","IcapeAtp##11","icape_high");
$cursor=ora_open($dbcon);

ora_parsE($cursor,"create table test (a number)");
ora_Exec($cursor);
echo "create done?\n";

ora_parse($cursor,"insert into test values (3)");
ora_Exec($cursor);
echo "insert done?\n";

ora_parse($cursor,"Select * from test");
ora_exec($cursor);
while (ora_Fetch($cursor))
		echo "Fetched: ".getdata($cursor,0)."\n";


?>
