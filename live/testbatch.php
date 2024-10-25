
<html>
	<head><link type="text/css" rel="stylesheet" href="style.css"><title>PUT YOUR TITLE HERE</title><head>
	
	<body>
	<? require_once ( "menu.inc" ); ?>
	<?
    require_once("serial.inc");
    require_once("error.inc");
    require_once("stock.inc"); // this might not be needed
    require_once("../php3/oracle.inc");
    require_once("../php3/sec.inc");
    require_once("../php3/misc.inc"); 

    if( !open_oracle() )
    	exit;

	if( !AllowedAccess( "" ) )
		exit;

                        $mailto="";
                        ora_parse($cursor,"select A.email from user_details A, user_pages B where A.user_serial=B.user_serial and B.page_name='CREDITORS_DOWNLOAD' and A.is_current in ('Y','L') ");
                        ora_exec($cursor);
                        while (ora_fetch($cursor))
                        {
                                if ($mailto!="")
                                        $mailto.=",";
                                $mailto.=getdata($cursor,0);
                        }
			echo $mailto;
                        mail($mailto,"New Payaccsys batch","Please see http://192.168.10.4/move/move_batch_view.phtml?exp=yes");


	close_oracle();
?>
</body>
</html>

