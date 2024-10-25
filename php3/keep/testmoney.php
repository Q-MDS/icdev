<?php
require_once ("../php3/oracle.inc");
require_once ("../php3/misc.inc");


if (!open_oracle()) { Exit; };

$to="US Dollar";
$from="S.A. Rand";
$amt=100;

echo "$from $amt = $to ";
echo convert_money_sage($from,$to,$amt);
echo "\n";

?>
<?php close_oracle() ?>
