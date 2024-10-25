<?php


define ("BASEPATH",".");



/**
 * System SMS Management Class
 *
 * Lets you send sms from the system
 *
 * @package		CodeIgniter
 * @subpackage		Libraries
 * @category		System User
 * @author		Intercape Dev Team
 */
class CI_Sms {
    // --------------------------------------------------------------------
    var $cellno = "";
    var $message = "";
    var $xml = "";
    var $CI = "";
    var $serial = "";
    var $serials = array();
    var $options = array();
    var $originator = "Ignite";
    var $reference = "";
    var $customdata = "";
    var $coach_serial = "";
    var $replyto = "";
    var $classification = "marketing";
	    
    // --------------------------------------------------------------------
    public function __construct() {
	// init
    }
    // --------------------------------------------------------------------
    public function clear_fields() {
	// init
	$this->cellno = "";
	$this->message = "";
	$this->xml = "";
	$this->serial = "";
	$this->originator = "Ignite";
	$this->reference =  "";
	$this->serials = array();
	$this->options = array();
	$this->customdata = "";
   	$this->coach_serial = "";
	$this->replyto = "";
    }
    // --------------------------------------------------------------------
    public function set_classification($classification) {

		$classification=strtoupper(substr($classification,0,1));
		if ($classification=="O")
			$this->classification="operational";
		else
			$this->classification="marketing";
	}
    // --------------------------------------------------------------------
    public function format_cell_number($cellno) {

	if (substr($cellno,0,2)=="00")
       		 $cellno="+".substr($cellno,2,999);

	if ($cellno[0]=="+")
        	$cellno=substr($cellno,1,999);

	if (substr($cellno,0,2)=="27" || $cellno[0]=="0") {

      	  if ($cellno[0]=="0")
       	 {
                $checkit=$cellno[1];
                $checkit2=$cellno[2];
        	}
        else
       	 {
                $checkit=$cellno[2];
                $checkit2=$cellno[3];
         }
         if ($checkit<6)
                return false; // not a SA cell number
         if ($checkit=="8" && ($checkit2=="7" || $checkit2=="6" || $checkit2=="0"))
                return false;   // not a SA cell number

	return $cellno;

}


if (!is_numeric($cellno))
        return false; // NOT A NUMBER!!

    }
    private function process_options() {

	if (isset($this->options["Reference"]))
		$this->reference = $this->options["Reference"];

	if (isset($this->options["Originator"]))
                $this->originator = $this->options["Originator"];

	if (isset($this->options["CustomData"]))
		$this->customdata = $this->options["CustomData"];

	if (isset($this->options["CoachSerial"]))
                $this->coach_serial = $this->options["CoachSerial"];

	if (isset($this->options["ReplyTo"]))
	{
                $this->replyto = substr($this->options["ReplyTo"],0,1);
		echo "replyto now ".$this->replyto."\n";
	}


    }
    // --------------------------------------------------------------------
    public function send_sms($cellno, $message, $options = array()) {
	// init
	$defaults = array(
	    
	);
	$options = array_replace_recursive($defaults, $options);

	$this->options = $options;
	$this->process_options();

	// assign variables
	$this->cellno = $cellno;
	$this->message = $message;
	
	// setup xml and verify data
	$this->construct_xml();
	
	// send the message
	return $this->send_to_grapevine();
    }
    // --------------------------------------------------------------------
    public function xml_escape_string($string) {

	$string = str_replace("&","&amp;",$string);
	$string = str_replace("<","&lt;",$string);
	$string = str_replace(">","&gt;",$string);
	$string = str_replace("%","&#37;",$string);
	
	return $string;
    }

    // 
    public function construct_xml() {
	global $cursor;
	// init

	$uri = $_SERVER['REQUEST_URI'];
	$uri = $this->xml_escape_string( $uri);

	$reference_encoded = $this->xml_escape_string( $this->reference);
	$originator_encoded = $this->xml_escape_string( $this->originator);


	$message_encoded = $this->xml_escape_string( $this->message);

	if (is_array($this->cellno)) { // multiple recipients
		reset($this->cellno);
		$recipients="";
		while (list($key,$val)=each($this->cellno)) {

	                //$serial = $this->CI->db->select("SELECT sms_id.nextval next_serial FROM DUAL");
			ora_parse($cursor,"select sms_id.nextval next_serial FROM DUAL");
			ora_exec($cursor);
			ora_fetch($cursor);
			$this->serial=getdata($cursor,0);
		
			$this->serials[$key] = $this->serial; // store in an array

			$cellno=$this->format_cell_number($this->cellno[$key]);

			if (is_numeric($cellno)) {
				$recipients.='<recipient>
<msisdn>'.$cellno.'</msisdn>
</recipient>';
			}

//                        <customData><serial>'.$this->serials[$key].'</serial><reference>'.$reference_encoded.'</reference><uri>'.$uri.'</uri>'.$this->customdata.'</customData>
 //                   </recipient>
  //    	            ';
 			
		} // while
//    <transmitDateTime>'.date("Y-m-d", strtotime("now")).'T'.date("H:i:s", strtotime("now")).'</transmitDateTime>

		$rules='<transmissionRules>
	<transmitDateTime>'.date("Y-m-d", strtotime("now")).'T'.date("H:i:s", strtotime("now")).'</transmitDateTime>
        <transmitPeriod>
            <startHour>6</startHour>
            <endHour>23</endHour>
        </transmitPeriod>
    </transmissionRules>'; //rules
	} else { // single recipient

		$cellno = $this->format_cell_number($this->cellno);
		//$serial = $this->CI->db->select("SELECT sms_id.nextval next_serial FROM DUAL");
		ora_parse($cursor,"select sms_id.nextval next_serial FROM DUAL");
                ora_exec($cursor);
                ora_fetch($cursor);
                $serial=getdata($cursor,0);

		$this->serial = $serial[0]->next_serial;
		$recipients='<recipient>
<msisdn>'.$cellno.'</msisdn>
<customData><serial>'.$this->serial.'</serial><reference>'.$reference_encoded.'</reference><uri>'.$uri.'</uri></customData>
</recipient>';
		$rules="";
		
	} // single

	if ($this->classification=="operational")
	{
		$logincode="INTERCAPEOPERATIONAL";
		$afcode="INT011-001";
	}
	else
	{
		$logincode="INTERCAPEFMAPPLINK";	
		$afcode="INT011";
	}
	$this->xml = '<?xml version="1.0" encoding="UTF-8"?'.'>
<gviSmsMessage>
<affiliateCode>'.$afcode.'</affiliateCode>
<authenticationCode>'.$logincode.'</authenticationCode>
<submitDateTime>'.date("Y-m-d", strtotime("now")).'T'.date("H:i:s", strtotime("now")).'</submitDateTime>
<originator>'.$originator_encoded.'</originator>
<messageType>text</messageType>
<recipientList>
<message>'.$message_encoded.'</message>
'.$recipients.'</recipientList>
'.$rules.'
</gviSmsMessage>';
    }
    // --------------------------------------------------------------------
    public function send_to_grapevine() {
	global $cursor;

	// init
	if ($this->classification=="operational")
		$url = "http://www.gvi.bms27.vine.co.za/httpInputhandler/ApplinkUpload";
	else
		$url = "http://www.gvi.bms9.vine.co.za/httpInputhandler/ApplinkUpload";
	
	echo $this->xml;

	$input_xml = $this->xml;
	$input_xml = str_replace("\r", "", $input_xml);
	$input_xml = str_replace("\n", "", $input_xml);
	$input_xml = str_replace("\t", "", $input_xml);
	$input_xml = str_replace("                ", "", $input_xml);


	echo "\n\n";
	echo $this->xml;
	echo "----\n";

	
	// send curl
	$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "XML=".urlencode($input_xml));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        $data = curl_exec($ch);
        curl_close($ch);

	// remove password in message
	$message = $test = preg_replace("/Password:[ ](.*)[-]/", "Password: *** -", $this->message);
	 $message=str_replacE("'","",$message);
	
	if (isset($cursor)) {
	// save sent entry
	  $nowtime=date("YmdHis", strtotime("now"));
	  $username=getenv("REMOTE_USER");
	  if (is_array($this->cellno)) { // multiple
		reset( $this->cellno);
		while( list( $key, $val) = each( $this->cellno)) {
			$qry="INSERT INTO sms_sent (serial, date_time, number_to, message, reference, coach_serial, replyto, sent_by) VALUES (".$this->serials[$key].", to_Date('$nowtime','YYYYMMDDHH24MISS'), '".$this->cellno[$key]."', '$message', '$this->reference', '$this->coach_serial', '$this->replyto', '$username' )";
			echo "$qry<bR>\n";
			ora_parse($cursor,$qry);
			ora_exec($cursor);		
		} // while
	  } else { // single message
		$qry="INSERT INTO sms_sent (serial, date_time, number_to, message, reference) VALUES ($this->serial, to_Date('$nowtime','YYYYMMDDHH24MISS'), '$this->cellno', '$message', '$this->reference', '$this->coach_serial', '$this->replyto', '$username' )";
		echo "$qry<br>\n";
		ora_parse($cursor,$qry);
                ora_exec($cursor);
	  }

	} // isset
	

	// response data
	$array_data = json_decode(json_encode(simplexml_load_string($data)), true);
	
	if($array_data["resultCode"] == 0){
		echo "SENT<Br>\n";	
	    $this->clear_fields();
	    return true;
	}else{
		echo "FAIL<bR>\n";
		var_dump($array_data);
		var_dump($data);
	    //console("SMS SENT FAILED ------------------------");
	    //console($this->xml);
	    $this->clear_fields();
	    return false;
	}
    }
    // --------------------------------------------------------------------
}

/* End of file Table.php */
/* Location: ./system/libraries/Table.php */
?>
