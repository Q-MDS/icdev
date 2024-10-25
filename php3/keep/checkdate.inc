<?
/* ******************************************************************* */
/* ******** All Non-php date functions I created are here ************ */
/* ******** They are extremely basic.                     ************ */
/* ******************************************************************* */
function checktime($xtime)
{
	if ($xtime=="NONE") { return $xtime; };

	if (strstr($xtime,":")) {
		$regs=explode(":",$xtime);
		$hours = $regs[0];
		$mins =  $regs[1];
	} else {
		$hours = substr($xtime,0,2);
		$mins  = substr($xtime,2,2);
	}

	if ($hours>24 || $hours<0 || !is_numeric($hours))
		{ return -1; };

	if ($mins>60 || $mins<0 || !is_numeric($mins))
		{ return -2; };

	$hours=sprintf("%02d",$hours);
	$mins=sprintf("%02d",$mins);

	$newtime = $hours.$mins;

	if (strlen($newtime)!=4)
		{ return -3; };

	return $newtime;
}

function checkyear($y)
{
	settype($y,"string");
	if (strlen($y)<4):
		return false;
	endif;
	return true;
}

function checkmonth($m)
{
	if (strlen($m)<>2):
		return false;
	endif;
	settype($m,"integer");
	if ($m<1 || $m>12):
		return false;
	endif;
	return true;
}

function checkday($d,$m,$y)
{
	settype($d,"integer");
	settype($m,"integer");
	settype($y,"integer");

	if ($d<1 || $d>31) return false;

	if ($m==2 || $m==4 || $m==6 || $m==9 || $m==11):
		if ($d>30):
			return false;
		endif;
	endif;

	if ($m==2):
		if (($y%4)==0):
			if ($d>29):
				 return false;
			endif;
		else:
			if ($d>28):
				return false;
			endif;
		endif;
	endif;

	return true;
}

function check_url($url)
{
	if (strlen($url)<8):
		return false;
	endif;
	return true;
}

?>
