<?php    
    require_once("functions.inc");
    require_once("../../php3/oracle.inc");        
    require_once("../../php3/colors.inc");    
        if (!open_oracle()) { Exit; };    
    echo checkIfAuditored( "40" );
  
    ?>
    
