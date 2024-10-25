<?
function checktime($xtime)
{
	if ($xtime=="NONE") { return $xtime; };

	if (ereg( "([0-9]{1,2})([a-z,:]{1})([0-9]{1,2})", $xtime, $regs )):
		$hours = $regs[1];
		$mins =  $regs[3];
	else:
		$hours = substr($xtime,0,2);
		$mins  = substr($xtime,2,2);
	endif;

	if ($hours>24 || $hours<0)
		{ return -1; };

	if ($mins>60 || $mins<0)
		{ return -2; };

	$newtime = $hours.$mins;

	if (strlen($newtime)!=4)
		{ return -3; };

	if (ereg("([a-z,:]{4})", $newtime, $regs))
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
