
<?

if (!isset($conn))
{
require("../php3/oracle.inc");
require("../php3/misc.inc");

if (!open_oracle()) { echo "Could not open Oracle. Leaving comments out<br>";  $nooracle=true;  };

}
$repcur=ora_open($conn);

function comments($routeno,$month,$year,$repcode,$args="")
{
global $repcur,$nooracle;



if ($nooracle)
	return;

while (strlen($routeno)<4)
	$routeno="0".$routeno;


$qry="select to_char( timestamp, 'DD Mon YYYY HH24:MI' ),who,comments from report_comments where report_id=$repcode and month=$month and year=$year and route='$routeno'";
if ($args!="")
	$qry.=" and report_args='$args' ";
$qry.=" order by timestamp";

//echo $qry;

ora_parse($repcur,$qry);
ora_exec($repcur);
echo "<table border=1 cellspacing=0>";
while (ora_fetch_into($repcur,$data)):
	echo "<tr bgcolor=lightblue><td><font size=2>$data[0]</td><td><font size=2>$data[1]</td><td><font size=2>$data[2]</td></tr>";
	unset($data);
endwhile;
echo "<tr bgcolor=lightpink><td colspan=3><font size=2><a onclick=\"addit=window.open('add_comment.phtml?route=$routeno&month=$month&year=$year&repcode=$repcode&args=$args','addit','top=20,left=20,width=500,height=200,resizable=yes,scrollbars=yes'); addit.focus(); \">Click here to add a comment</a></td></tr>";

echo "</table>";



}


?>
