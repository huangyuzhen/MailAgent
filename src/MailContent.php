<?php
/**
* smtpchat 实现smtp协议对话过程，直接投递
*/

namespace MailAgent;

class MailContent
{
    private $boundary  = '';
    private $messageId        = '';

    private $txt_content = '';

    public function __construct()
    {
        // $this->boundary  = $this->genBoundary();
        // $this->messageId = $this->genMessageId();
    }

    public function getBoundary()
    {
        return $this->boundary;
    }

    public function genBoundary()
    {
        $boundary = "=_".md5(time())."_";

        $this->boundary = $boundary;
        return $boundary;
    }

    public function genMessageId()
    {
        static $i;
        if ($i <= 0) {
            $i=rand() % 10000;
        }
        $i++;
        return date("YmdHis")."-$i@sender";
    }

    public function qpEncode($content)
    {
        return quoted_printable_decode($content);
    }

    public function b64Encode($content)
    {
        $string = base64_encode($content);
        $result ='';
        for ($i=0; $i<strlen($string); $i++) {
            $result.= substr($string, $i, 1);
            if ($i%76 == 75) {
                $result.= "\r\n";
            }
        }
        $result.= "\r\n";
        return $result;
    }

    /*
     * type: text/plain, text/html
     * charset: utf-8
     * encoding: base64, quoted-printable
     */
    public function mimeHeader($type = 'text/plain', $charset = 'utf-8', $encoding = 'base64')
    {
        $header = "Content-Type: $type; charset=\"$charset\"\n";
        $header.= "Content-Transfer-Encoding: $encoding\n";

        return str_replace("\n", "\r\n", $header);
    }

    /*
     * subject
     */
    public function encodeMimeContent($content, $charset = 'UTF-8', $encoding = 'base64')
    {
        $output = '=?'. $charset. '?';
        if ($encoding == 'base64') {
            $output.= 'B?';
            $output.= base64_encode($content);
        } else {
            $output.= 'Q?';
            $output.= quoted_printable_decode($content);
        }

        $output.= '?=';
        return $output;
    }

    public function getMailContentFromTemplate($data, $template_filename)
    {
        $file_content = file_get_contents($template_filename);

        $data['nowDate']   = date('r');
        $data['messageId'] = $this->genMessageId();

        $formated_content = $this->contentFormat($data, $file_content);

        return $formated_content;
    }

    public function contentFormat($data, $content)
    {
        $pattern = '/\{\{\$([a-zA-z0-9_]+)\}\}/';
        return preg_replace_callback($pattern, function ($a) use ($data) {
            $key = $a[1];
            if (isset($data[$key])) {
                return $data[$key];
            } else {
                return '';
            }
        }, $content);
    }
}
