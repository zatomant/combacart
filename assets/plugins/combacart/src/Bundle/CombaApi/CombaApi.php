<?php

namespace Comba\Bundle\CombaApi;

use Comba\Core\Logs;
use RemoteIP;
use function get3thAuth;

/**
 * Comba API Class.
 *
 * @author zatomant
 *
 */
class CombaApi
{

    private string $_url;
    private string $_key;

    public function __construct()
    {
        $auth = get3thAuth('Comba');
        if (!empty($auth)) {
            $this->setUrl($auth['url'])->setKey($auth['key']);
        }
    }

    public function request(string $method, $params = null, string $curl_method = 'POST')
    {
        $data = [
            'ip' => $this->getIpAddr(),
            'calledMethod' => $method,
            'methodProperties' => $params,
        ];

        // використовуємо вбудований сервер без виклику curl
        if (defined('COMBAMODX_BUILDIN_SERVER') && COMBAMODX_BUILDIN_SERVER) {
            define('AUTHPASS', true);
            $_externaldata = json_encode($data);
            require dirname(__FILE__, 2) . '/BuildInServer/build-in_server.php';
            return $this->prepare($ret);
        }

        $curl_options = array(
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_URL => $this->getUrl(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_HTTPHEADER => array(
                'User-Agent: Comba Modx (' . COMBAMODX_SERVER_NAME . ')',
                'Authorization: Bearer ' . $this->getKey(),
                'Content-Type: application/json;charset=utf8'
            ),
        );

        if ($curl_method == 'POST') {
            $curl_options[CURLOPT_POST] = true;
            $curl_options[CURLOPT_POSTFIELDS] = json_encode($data);
        } else {
            $data = http_build_query($data, '', '&');
            $curl_options[CURLOPT_URL] = $this->getUrl() . "?" . $data;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $lg = new Logs();
            $response = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $lg->save('Error response: ' . $response);
        }
        curl_close($ch);

        return $this->prepare($result);
    }

    public function getIpAddr(): ?string
    {
        $curip = (new RemoteIP())->get_ip_address();
        return (empty($curip) && strlen($curip) < 8) ? 'no-ip' : $curip;
    }

    public function getUrl(): string
    {
        return $this->_url;
    }

    public function setUrl($url): CombaApi
    {
        $this->_url = filter_var($url, FILTER_SANITIZE_URL);
        return $this;
    }

    public function getKey(): string
    {
        return $this->_key;
    }

    public function setKey($key): CombaApi
    {
        $this->_key = htmlspecialchars($key);
        return $this;
    }

    private function prepare($data)
    {
        return $data;
    }

}
