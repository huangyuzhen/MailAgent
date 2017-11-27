<?php
/**
* mail sender
*/

namespace MailAgent;

class MailSender
{
    private $smtp_client;

    public function __construct()
    {
        $this->smtp_client = new SmtpChat();
    }

    public function sendto($to, $mail_content)
    {
        $flag = false;
        list($account, $domain) = explode('@', $to);

        $all_ips = MxRecord::getMxIps($domain);
        $try_n   = 0;
        foreach ($all_ips as $ip) {
            $flag = $this->smtp_client->smtpSendmail($to, $mail_content, $ip);
            if (!$flag) {
                $error = $this->smtp_client->getError();
                if ($error['error_code'] != 107) {
                    $try_n ++;
                }
            } else {
                // finished.
                break;
            }

            if ($try_n >= 1) {
                break;
            }
            usleep(100);
        }

        return $flag;
    }
}
