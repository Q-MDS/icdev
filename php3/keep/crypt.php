<?

  function obfuscate_data($input,$debug=false) {

  $plaintext=$input;
  $key="WeDoBuses";

	if ($debug)
		echo "<pre>";

  $key = substr(sha1($key, true), 0, 16);
  $iv = base64_encode(openssl_random_pseudo_bytes(16));
  $iv = substr($iv,0,16);

  $ciphertext = openssl_encrypt($plaintext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
  if ($debug)
	  echo "Crypt is ".md5($ciphertext)."\n";
 $ciphertext=base64_encode($ciphertext);
  $len1=strlen($ciphertext);
  $len2=strlen($iv);
  if ($debug)
	echo "Ciphertext is $ciphertext ($len1)\niv is $iv ($len2)\n</pre>";

  
 return $iv.$ciphertext; 

 }


 function de_obfuscate_data($ciphertext,$debug=false) {

 $iv=substr($ciphertext,0,16);
 $ciphertext=substr($ciphertext,16,9999);

  $len2=strlen($ciphertext);
  $len1=strlen($iv);
  if ($debug)
	   echo "<pre>IV is $iv ($len1)\nCiphertext is $ciphertext ($len2)\n";
   $ciphertext=base64_decode($ciphertext);

 $key="WeDoBuses";
  $key = substr(sha1($key, true), 0, 16);

if ($debug) {
	echo "Crypt: ";
	echo md5($ciphertext)."\n";
  }
 $plaintext = openssl_decrypt($ciphertext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
 if ($debug) 
	 echo "\n\n\n";
 if ($debug)
	 echo "(1) Result is $plaintext\n</pre>";
 return $plaintext;

 }


/*
 $cc="1234123412341234";
 $crypt=obfuscate_data($cc);
 //echo "\n\nGot crypt $crypt\n\n";
 echo de_obfuscate_data($crypt)."\n";
*/

?>
