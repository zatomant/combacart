<?php

namespace Comba\Bundle\CombaApi;

use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Comba\Core\Entity;
use Comba\Core\Logs;
use RemoteIP;
use function Comba\Functions\array_search_by_key;

/**
 * Comba API Class
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
        $auth = Entity::get3thAuth('Comba', 'marketplace');
        if (!empty($auth)) {
            $this->setUrl($auth['url'])->setKey($auth['key']);
        }
    }

    public function request(string $method, $params = null, string $httpMethod = 'POST')
    {
        $data = [
            'ip' => $this->getIpAddr(),
            'calledMethod' => $method,
            'methodProperties' => $params,
        ];

        // запит до локального "сервера"
        if (array_search_by_key(Entity::getData('BUILDIN_SERVER'), 'enabled') == true) {
            define('AUTHPASS', true);
            $_externaldata = json_encode($data);
            require dirname(__FILE__, 2) . '/BuildInServer/build-in_server.php';
            return $this->prepare($ret);
        }

        // запит до віддаленого сервера
        $httpClient = Psr18ClientDiscovery::find();
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        // Якщо клієнт підтримує налаштування таймауту (наприклад, Guzzle)
        if (method_exists($httpClient, 'withOptions')) {
            $httpClient = $httpClient->withOptions([
                'timeout' => 10, // Загальний таймаут (секунди)
                'connect_timeout' => 5, // Таймаут на встановлення з'єднання
            ]);
        }

        $url = $this->getUrl();
        $headers = [
            'User-Agent' => 'Comba Modx (' . Entity::getServerName() . ')',
            'Authorization' => 'Bearer ' . $this->getKey(),
            'Content-Type' => 'application/json;charset=utf8'
        ];

        if ($httpMethod === 'POST') {
            $body = $streamFactory->createStream(json_encode($data));
            $request = $requestFactory->createRequest('POST', $url)
                ->withBody($body)
                ->withHeader('HTTP_VERSION', '2.0');
        } else {
            $queryString = http_build_query($data);
            $request = $requestFactory->createRequest('GET', $url . '?' . $queryString)
                ->withHeader('HTTP_VERSION', '2.0');
        }

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        try {
            $response = $httpClient->sendRequest($request);
            return $this->prepare($response->getBody()->getContents());
        } catch (ClientExceptionInterface $e) {
            $lg = new Logs();
            $lg->save('HTTP Request Error: ' . $e->getMessage());
            return null;
        }
    }

    public function getIpAddr(): ?string
    {
        $curip = (new RemoteIP())->get_ip_address();
        return (empty($curip) && strlen($curip) < 8) ? 'no-ip' : $curip;
    }

    private function prepare($data)
    {
        return $data;
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

}
