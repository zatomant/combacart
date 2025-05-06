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
use Comba\Core\RateLimiter;

require_once '../vendor/autoload.php';

$ip = (new RemoteIP)->get_ip_address();
$lg = new Logs('api');
$lg->setGlobalContext(['ip' =>$ip]);
$headers = getallheaders();

$rateLimiter = new RateLimiter();

if ($rateLimiter->isBlocked($ip)) {
    $rateLimiter->handleBlock("Забагато спроб API авторизації", 429, "Забагато спроб API авторизації IP $ip");
}

if (!isset($headers['Authorization'])) {
    $rateLimiter->registerStatusAttempt($ip, false)
        ->handleError("Authorization wrong", null, "Заголовок Authorization не знайдено, IP $ip.");
}

$authHeader = $headers['Authorization'];
$token = str_replace('Bearer ', '', $authHeader);
if (!preg_match('/^[A-Za-z0-9]{20,32}$/', $token)) {
    $rateLimiter->registerStatusAttempt($ip, false)
        ->handleError("Invalid token", null, "Токен має невірний формат");
}

if (!in_array($token, array_keys(Entity::get3thAuth('RequestApi', 'marketplace')))) {
    $rateLimiter->registerStatusAttempt($ip, false)
        ->handleError("Token not found", '401 Unauthorized', "Токен $token не знайдено.");
}

$ret = null;
$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data)) {

    $callme = htmlspecialchars($data['calledMethod']);
    $lg->log('INFO', 'api method ' . $callme);
    $lg->log('INFO', json_encode($data['methodProperties']));

    if (empty($callme)) {
        $lg->log('ERROR','{"result":"wrong method"}');
        exit;
    }

    define('MODX_API_MODE', true);
    require_once dirname(__FILE__, 5) . "/index.php";
    $modx->db->connect();
    if (empty($modx->config)) $modx->getSettings();

    if ('ProductList' == $callme) {
        $doc = new ModxProduct(null, $modx);
        $ret = json_encode(['Document' => $doc->getPageInfo($data['methodProperties'])], JSON_UNESCAPED_UNICODE);
    }
    if ('ProductActivate' == $callme) {
        $doc = new ModxProduct(null, $modx);
        $_result = $doc->setAvailable($data['methodProperties']);
        if ($_result == 'ok') {
            (new CombaHelper(null, $modx))->updateReferenceProduct($data['methodProperties']['Document']['contentid']);
        }
        $ret = json_encode(['Document' => ['result' => $_result]], JSON_UNESCAPED_UNICODE);
    }
    if ('ProductDeactivate' == $callme) {
        $doc = new ModxProduct(null, $modx);
        $_result = $doc->setAvailable($data['methodProperties'], 0);
        if ($_result == 'ok') {
            (new CombaHelper(null, $modx))->updateReferenceProduct($data['methodProperties']['Document']['contentid']);
        }
        $ret = json_encode(['Document' => ['result' => $_result]], JSON_UNESCAPED_UNICODE);
    }
    if ('ProductUpdateImages' == $callme) {
        $doc = new ModxProduct(null, $modx);
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

