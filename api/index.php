<?php

/**
 * API Incoming Requests
 *
 * @category    PHP
 * @package     CombaCart
 */

use Comba\Bundle\CombaHelper\CombaHelper;
use Comba\Bundle\Modx\ModxResource;
use Comba\Bundle\Modx\ModxProduct;
use Comba\Core\Entity;
use Comba\Core\Logs;

ini_set('display_errors', 0); // Заборонити вивід помилок на екран
error_reporting(E_ALL);

require_once '../vendor/autoload.php';

$isAuth = false;
$lg = new Logs('api');
$lg->setExtendedData((new RemoteIP)->get_ip_address());
$headers = getallheaders();

// Перевірка наявності  Authorization
if (isset($headers['Authorization'])) {
    $authHeader = htmlspecialchars(strip_tags($headers['Authorization']));
    $token = str_replace('Bearer ', '', $authHeader);
    if (preg_match('/^[A-Za-z0-9]{20,32}$/', $token)) {
        if ($token) {

            $memcached = new Memcached();
            $memcached->addServer('localhost', 11211);
            $maxAttempts = 5;
            $attemptsKey = md5($token);

            // Перевірка токена серед дозволених до авторизації
            if (in_array($token, array_keys(Entity::get3thAuth('RequestApi','marketplace')))) {
                $isAuth = true;
                $memcached->delete($attemptsKey);
            } else {
                $attempts = (int) $memcached->get($attemptsKey); // Отримуємо кількість спроб з Memcached

                if ($attempts >= $maxAttempts) {
                    $lg->save("Забагато невдалих спроб авторизації з токеном $token.");
                    // http_response_code(429); // Відповідь "Забагато запитів" (HTTP 429)
                    exit;
                }

                $attempts++;
                $memcached->set($attemptsKey, $attempts, 3600); // Зберігаємо кількість спроб на 1 годину
                $lg->save("Токен $token не знайдено.");
            }
        }
    } else {
        $lg->save("Токен $token не знайдено.");
    }
} else {
    $lg->save("Заголовок Authorization не знайдено.");
}

if (!$isAuth) {
    return '';
}

$ret = null;
$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data)) {

    $callme = htmlspecialchars($data['calledMethod']);
    $lg->save('api method ' . $callme);
    $lg->save(json_encode($data['methodProperties']));
    if (empty($callme)) {
        $lg->save('{"result":"wrong method"}');
        exit;
    }

    define('MODX_API_MODE', true);
    require_once dirname(__FILE__, 5) . "/index.php";
    $modx->db->connect();
    if (empty($modx->config)) $modx->getSettings();

    if ('ProductList' == $callme) {
        $doc = new ModxProduct($modx);
        $ret = json_encode(['Document' => $doc->getPageInfo($data['methodProperties'])], JSON_UNESCAPED_UNICODE);
    }
    if ('ProductActivate' == $callme) {
        $doc = new ModxProduct($modx);
        $_result = $doc->setAvailable($data['methodProperties']);
        if ($_result == 'ok') {
            (new CombaHelper($modx))->updateReferenceProduct($data['methodProperties']['Document']['contentid']);
        }
        $ret = json_encode(['Document' => ['result' => $_result]], JSON_UNESCAPED_UNICODE);
    }
    if ('ProductDeactivate' == $callme) {
        $doc = new ModxProduct($modx);
        $_result = $doc->setAvailable($data['methodProperties'], 0);
        if ($_result == 'ok') {
            (new CombaHelper($modx))->updateReferenceProduct($data['methodProperties']['Document']['contentid']);
        }
        $ret = json_encode(['Document' => ['result' => $_result]], JSON_UNESCAPED_UNICODE);
    }
    if ('ProductUpdateImages' == $callme) {
        $doc = new ModxProduct($modx);
        $doc->prepareImages($data['methodProperties']);
        $ret = json_encode(['Document' => ['result' => 'ok']]);
    }
    if ('ClearCache' == $callme) {
        $doc = new ModxResource($modx);
        $doc->clearCache('full');
        $ret = json_encode(['Document' => ['result' => 'ok']]);
    }
}

if (!empty($ret)) {
    echo $ret;
}

