<?php
/**
* smtpchat 实现smtp协议对话过程，直接投递
*/

namespace MailAgent;

class SmtpChat
{
    private $hello_host = 'mx1';
    private $mail_from  = 'no-reply@example.com';

    private $error_code = 0;
    private $message    = '';

    public function __construct()
    {
        $args = func_get_args();
        $nArg = func_num_args();
        if ($nArg >= 2) {
            $this->hello_host = $args[0];
            $this->mail_from  = $args[1];
        } elseif ($nArg >= 1) {
            $this->hello_host = $args[0];
        }
    }

    private function setError($error_code, $message)
    {
        $this->error_code = $error_code;
        $this->message    = $message;
    }

    public function getError()
    {
        return array(
            'error_code' => intval($this->error_code),
            'message'    => strval($this->message),
        );
    }

    private function parseServerResponse($fp)
    {
        $line = '-';
        while (true) {
            $line = fgets($fp, 1024);
            echo "$line";
            if ($line === false) {
                $line = '603 socket reading error.';
                break;
            }
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }

        return $line;
    }

    private function responseIsOK($line)
    {
        $code = intval(substr($line, 0, 3));
        return ( $code >= 200 && $code < 300 );
    }

    private function responseEqualCode($line, $rescode)
    {
        $code = intval(substr($line, 0, 3));
        return ($code == $rescode);
    }

    private function chat($fp, $to, $content)
    {
        $res = $this->parseServerResponse($fp);
        if (! $this->responseIsOK($res)) {
            $this->setError(101, $res);
            return false;
        }

        $chat_array = array(
            "EHLO ". $this->hello_host ,
            "MAIL FROM: ". $this->mail_from,
            "RCPT TO: <$to>",
            );

        for ($i=0; $i<count($chat_array); $i++) {
            $chat = $chat_array[$i];
            fwrite($fp, $chat."\r\n");
            $res = $this->parseServerResponse($fp);
            if (! $this->responseIsOK($res)) {
                $this->setError(102 + $i, $res);
                return false;
            }
        }

        fwrite($fp, "DATA\r\n");
        $res= $this->parseServerResponse($fp);
        if (! $this->responseEqualCode($res, 354)) {
            $this->setError(105, $res);
            return false;
        }

        fwrite($fp, $content);
        fwrite($fp, "\r\n\r\n.\r\n");

        $res= $this->parseServerResponse($fp);
        if (! $this->responseIsOK($res)) {
            $this->setError(106, $res);
            return false;
        }

        fwrite($fp, "QUIT\r\n");
        $this->setError(0, $res);
        return true;
    }

    public function smtpSendmail($email_address_to, $content, $mx_server, $timeout = 30)
    {
        $remote_socket = "tcp://$mx_server:25";
        $timeout       = 30;

        $fp = stream_socket_client($remote_socket, $errno, $errstr, $timeout);
        if (!$fp) {
            $this->setError(107, "$errstr,  $errno");
            return false;
        }

        $res= $this->chat($fp, $email_address_to, $content);
        fclose($fp);

        return $res;
    }
}
