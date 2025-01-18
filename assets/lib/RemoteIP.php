<?php

class RemoteIP
{

    function get_ip_address(): ?string
    {
        // check for shared internet/ISP IP
        if (!empty(getenv('HTTP_CLIENT_IP')) && $this->validate_ip(getenv('HTTP_CLIENT_IP'))) {
            return getenv('HTTP_CLIENT_IP');
        }

        // check for IPs passing through proxies
        if (!empty(getenv('HTTP_X_FORWARDED_FOR'))) {
            // check if multiple ips exist in var
            if (strpos(getenv('HTTP_X_FORWARDED_FOR'), ',') !== false) {
                $iplist = explode(',', getenv('HTTP_X_FORWARDED_FOR'));
                foreach ($iplist as $ip) {
                    if ($this->validate_ip($ip))
                        return $ip;
                }
            } else {
                if ($this->validate_ip(getenv('HTTP_X_FORWARDED_FOR')))
                    return getenv('HTTP_X_FORWARDED_FOR');
            }
        }
        if (!empty(getenv('HTTP_X_FORWARDED')) && $this->validate_ip(getenv('HTTP_X_FORWARDED')))
            return getenv('HTTP_X_FORWARDED');
        if (!empty(getenv('HTTP_X_CLUSTER_CLIENT_IP')) && $this->validate_ip(getenv('HTTP_X_CLUSTER_CLIENT_IP')))
            return getenv('HTTP_X_CLUSTER_CLIENT_IP');
        if (!empty(getenv('HTTP_FORWARDED_FOR')) && $this->validate_ip(getenv('HTTP_FORWARDED_FOR')))
            return getenv('HTTP_FORWARDED_FOR');
        if (!empty(getenv('HTTP_FORWARDED')) && $this->validate_ip(getenv('HTTP_FORWARDED')))
            return getenv('HTTP_FORWARDED');

        // return unreliable ip since all else failed
        return getenv('REMOTE_ADDR');
    }

    /**
     * Ensures an ip address is both a valid IP and does not fall within
     * a private network range.
     */
    function validate_ip($ip): bool
    {
        if (strtolower($ip) === 'unknown')
            return false;

        // generate ipv4 network address
        $ip = ip2long($ip);

        // if the ip is set and not equivalent to 255.255.255.255
        if ($ip !== false && $ip !== -1) {
            // make sure to get unsigned long representation of ip
            // due to discrepancies between 32 and 64 bit OSes and
            // signed numbers (ints default to signed in PHP)
            $ip = sprintf('%u', $ip);
            // do private network range checking
            if ($ip >= 0 && $ip <= 50331647) return false;
            if ($ip >= 167772160 && $ip <= 184549375) return false;
            if ($ip >= 2130706432 && $ip <= 2147483647) return false;
            if ($ip >= 2851995648 && $ip <= 2852061183) return false;
            if ($ip >= 2886729728 && $ip <= 2887778303) return false;
            if ($ip >= 3221225984 && $ip <= 3221226239) return false;
            if ($ip >= 3232235520 && $ip <= 3232301055) return false;
            if ($ip >= 4294967040) return false;
        }
        return true;
    }

}
