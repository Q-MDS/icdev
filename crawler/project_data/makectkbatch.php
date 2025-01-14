<?php
require_once ("../php3/oracle.inc");
require_once ("../php3/misc.inc");

if (!open_oracle()) { Exit; };


/*
# EXAMPLE OF BATCH:
   JHB="BUS~6389" ; export JHB
   PE="BUS~6414" ; export PE

#./ctk_week $JHB $PE  "Johannesburg" "Port Elizabeth" 2105
#date >> /var/log/ctklog.log
#./ctk_week2 $JHB $MSB "Johannesburg" "Mossel Bay"  0102
#date >> /var/log/ctklog.log
*/

ora_parse($cursor,"select * from ctk_stops");
ora_exec($cursor);
$stops=array();
while (ora_fetch($cursor))
	$stops[getdata($cursor,0)]=getdata($cursor,1);

ora_parse($cursor,"select * from ctk_compare");
ora_exec($cursor);
$batch2="";
unset($data);
while (ora_fetch_into($cursor,$data)) {
	if (!isset($done[$data[0]][$data[1]])) {

		$done[$data[0]][$data[1]]=true; // avoid checking the same thing twice
		$from=$stops[$data[0]];
		$to=$stops[$data[1]];
	
		echo "./ctk_week BUS~$data[0] BUS~$data[1] \"$from\" \"$to\" $data[2]\n";

		$batch2.="./ctk_week2 BUS~$data[0] BUS~$data[1] \"$from\" \"$to\" $data[2]\n";


	}
	unset($data);
}// while

echo "./mailit\n";
echo $batch2;
echo "./mailit\n";
?>
