<?php

namespace MailAgent;

class MxRecord
{
    public static function getMx($domain)
    {
        $mx_hosts    = array();
        $mx_priority = array();
        $flag = getmxrr($domain, $mx_hosts, $mx_priority);
        if (! $flag) {
            return array();
        }

        $mx_server  = array();
        $ip_pattern = '/^(\d{1,3}\.){3}\d{1,3}$/';
        for ($i=0; $i<count($mx_hosts); $i++) {
            $host  = $mx_hosts[$i];
            if (preg_match($ip_pattern, $host)) {
                $ips    = array($host);
            } else {
                $a_list = dns_get_record($host, DNS_A);
                $ips    = array_column($a_list, "ip");
            }

            $array = array(
                'host'      => strval($mx_hosts[$i]),
                'priority'  => intval($mx_priority[$i]),
                'ip_addr'   => $ips,
            );

            $mx_server[] = $array;
        }

        return $mx_server;
    }

    public static function getMxRecord($domain)
    {
        $mx_server = MxRecord::getMx($domain);

        usort($mx_server, function ($a1, $a2) {
            $s1 = $a1['priority'];
            $s2 = $a2['priority'];
            if ($s1 == $s2) {
                return 0;
            } else {
                return $s1 < $s2 ? -1 : 1;
            }
        });

        return $mx_server;
    }

    public static function getMxIps($domain)
    {
        $mx_server = MxRecord::getMxRecord($domain);

        $all_ips  = array();
        foreach ($mx_server as $mx) {
            $ips = $mx['ip_addr'];
            foreach ($ips as $ip) {
                if (! in_array($ip, $all_ips)) {
                    $all_ips[] = $ip;
                }
            }
        }

        return $all_ips;
    }
}
