<?php

require 'vendor/autoload.php';


$b = new MailAgent\MailContent();
$boundary = $b->genBoundary();

$to = "tester@123.com";

$data=array(
    "to_email"  => "$to",
    "to"        => "tester <$to>",
    "subject"   => $b->encodeMimeContent('感谢参与'),
    "from"      => "<$to>",
    "boundary"  => $boundary,
    );

$filename = __DIR__ . "/template/header_multi.txt";
$header   = $b->getMailContentFromTemplate($data, $filename);

$filename = __DIR__ . "/template/mail.txt";
$txt      = $b->getMailContentFromTemplate($data, $filename);
$txt      = $b->b64Encode($txt);

$filename = __DIR__ . "/template/mail.html";
$html     = $b->getMailContentFromTemplate($data, $filename);
$html     = $b->b64Encode($html);

$content = $header;
$content.= "--". $boundary ."\r\n";
$content.= $b->mimeHeader();
$content.= "\r\n";
$content.= $txt;

$content.= "--". $boundary ."\r\n";
$content.= $b->mimeHeader("text/html");
$content.= "\r\n";
$content.= $html;

$content.= "--". $boundary ."--\r\n";

echo $content;


// $mailSender = new MailAgent\MailSender();
// $flag = $mailSender->sendto($data['to_email'], $content);

// echo $flag ? "ok" : "bad";
// echo "\n";
