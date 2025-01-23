<?php
require_once("class.html.mime.mail.inc");

$message = '';
// $email_list = ["keith@intercape.co.za"];
$email_list = ["quintin@pxo.co.za"];
$from = $noreply_email;
$subject = "CTK Crawl Error Report - " . date("Y-m-d H:i:s");
$html_message .= "Error list: <br>";
$text_message .= "Error list: \n";


$mail = new html_mime_mail('X-Mailer: Html Mime Mail Class');
$mail->add_html($html_message, $text_message);
$mail->build_message();

foreach ($email_list as $email_address) 
{
	$mail->smtp_send($from, $email_address);
}
?>