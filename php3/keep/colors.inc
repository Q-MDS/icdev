<?
/* ****************************************************************************** */
/* ******************* Gets the display colours from the database,   ************ */
/* ******************* This was commented out when the system was    ************ */
/* ******************* struggling, default values are just returned  ************ */
/* ******************* Probably should be added back in, but nobody  ************ */
/* ******************* seems to care about the colours yet.          ************ */
/* ****************************************************************************** */


function get_colors($tusername)
{
	global $table_bg, $table_title, $table_cell, $text_nb, $cursor, $table_alt;
        $table_bg="777777";
        $table_title="ccddee";
        $table_cell="fabdce";
        $text_nb="FF0000";
	$table_alt="cebdfa";
	return;
/*
	ora_parse($cursor,"select * from colors where user_serial in (select user_serial from user_details where username='$tusername' and is_current='Y')");
	ora_exec($cursor);
	if (!ora_fetch($cursor)):
        	ora_parse($cursor,"select * from colors where user_serial='DEFAULT'");
        	ora_exec($cursor);
        	if (!ora_fetch($cursor)):
                	$nocolors=true;
        	endif;
	endif;

	if ($nocolors):
        	$table_bg="537284";
        	$table_title="abefdg";
        	$table_cell="fabdce";
        	$text_nb="FF0000";
		$table_alt="cebdfa";
	else:
        	$table_bg=ora_getColumn($cursor,1);
        	$table_title=ora_getColumn($cursor,2);
        	$table_cell=ora_getColumn($cursor,3);
        	$text_nb=ora_getColumn($cursor,4);
		$table_alt=ora_getColumn($cursor,5);
	endif;
*/
}
?>
