<?php

//disable double quotes for anti-hacking purposes.

function checkWord($word){

	$word=str_replace('"',"",$word);
	$word=str_replace("`","",$word);
	$response = `grep -w "$word" /usr/share/dict/words`;
	
	if (trim($response) == trim($word)){return true;}else{return false;}
	
	
}


?>

