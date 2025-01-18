<?php

/**
 * Build-in server API
 *
 * @category    PHP
 * @package     CombaCart
 */


use Comba\Bundle\BuildInServer\Server;
use Comba\Core\Entity;
use Comba\Core\Logs;
use function Comba\Functions\array_search_by_key;

if (!defined('AUTHPASS')) {
    header('Location: //' . Entity::getServerHost());
    exit;
}

require_once dirname(__FILE__, 4) . '/vendor/autoload.php';
require_once dirname(__FILE__, 4) . '/src/Functions/Functions.php';

$lg = new Logs('api');
$lg->setExtendedData((new RemoteIP)->get_ip_address());

$data = json_decode(file_get_contents('php://input'), true);
$data = json_decode($_externaldata, true);

$ret = null;
//$lg->save(json_encode($data));

if (!empty($data)) {

    $callme = htmlspecialchars($data['calledMethod']);
    $lg->save('api method ' . $callme);
    $lg->save(json_encode($data['methodProperties']));
    if (empty($callme)) {
        return '{"result":"wrong method"}';
    }

    if ('CabinetRead' == $callme) {
        // перелік замовлень лише за email адресою
        if ($data['methodProperties']['Marketplace'] && $data['methodProperties']['User']['useremail']) {
            $res = array_search_by_key(
                (new Server())->documentList([
                    'search' => array_search_by_key($data['methodProperties']['User'], 'useremail')
                ]),
                'docs');
            $ret = !empty($res) ? '{"result":"ok","Document":' . json_encode($res) . '}' : '{"result":"error"}';
        }
    }
    if ('DocumentGetCurrentId' == $callme) {
        if ($data['methodProperties']['Document']) {
            $res = (new Server())->documentgetcurrentid($data['methodProperties']['Document']['session']);
            $ret = '{"result":"ok","Document":{"uid":"' . $res . '"}}';
        }
    }
    if ('DocumentCheckout' == $callme) {
        if ($data['methodProperties']['Document']) {
            $res = (new Server())->documentcheckout($data['methodProperties']);
            $ret = !empty($res) ? '{"result":"ok","Document":{"uid":' . json_encode($res) . '}}' : '{"result":"error"}';
        }
    }
    if ('DocumentNew' == $callme) {
        if ($data['methodProperties']['Document']) {
            $res = (new Server())->documentcreate($data['methodProperties']['Document']['session']);
            $ret = !empty($res) ? '{"result":"ok","Document":{"uid":"' . $res . '"}}' : '{"result":"error"}';
        }
    }
    if ('DocumentRead' == $callme) {
        if ($data['methodProperties']['Document']) {
            $res = (new Server())->documentread($data['methodProperties']['Document']['uid']);
            $ret = !empty($res) ? json_encode($res) : '{"result":"error"}';
        }
    }
    if ('DocumentSpecInsert' == $callme) {
        if ($data['methodProperties']['Document']) {
            $doc = new Server();
            $res = $doc->documentspecinsert($data['methodProperties']);
            $doc->documentupdatesum($data['methodProperties']['Document']['uid']);
            $ret = $res ? '{"result":"ok"}' : '{"result":"error"}';
        }
    }
    if ('DocumentSpecUpdate' == $callme) {
        if ($data['methodProperties']['Document']) {
            $doc = new Server();
            $res = $doc->documentspecupdate($data['methodProperties']);
            $doc->documentupdatesum($data['methodProperties']['Document']['uid']);
            $ret = $res ? '{"result":"ok"}' : '{"result":"error"}';
        }
    }
    if ('DocumentSpecDelete' == $callme) {
        if ($data['methodProperties']['Document']) {
            $doc = new Server();
            $res = $doc->documentspecdelete($data['methodProperties']);
            $doc->documentupdatesum($data['methodProperties']['Document']['uid']);
            $ret = $res ? '{"result":"ok"}' : '{"result":"error"}';
        }
    }
    if ('ProductUpdateCartSpecUpdateSum' == $callme) {
        if ($data['methodProperties']['Product']) {
            $doc = new Server();
            $res = $doc->documentupdatecart($data['methodProperties']);
            $ret = $res ? '{"result":"ok"}' : '{"result":"error"}';
        }
    }
    if ('Marketplace' == $callme) {
        if ($data['methodProperties']['uid']) {
            $res = (new Server())->marketplace($data['methodProperties']['uid']);
            $ret = !empty($res['uid']) ? '{"result":"ok","Document":' . json_encode($res) . '}' : '{"result":"error"}';
        }
    }
    if ('Seller' == $callme) {
        if ($data['methodProperties']['uid']) {
            $res = (new Server())->sellers($data['methodProperties']['uid']);
            $ret = !empty($res) ? '{"result":"ok","Document":' . json_encode($res) . '}' : '{"result":"error"}';
        }
    }
    if (in_array($callme, ['Payment', 'Tracking'])) {
        if ($data['methodProperties']['Document']) {
            $res = (new Server())->documenttracking($data['methodProperties']['Document']['uid']);
            $ret = !empty($res) ? json_encode($res) : '{"result":"error"}';
        }
    }
    if ('DeliveryList' == $callme) {
        if ($data['methodProperties']['Seller']) {
            $res = (new Server())->delivery();
            foreach ($res as $k => $el) {
                if (isset($el['disabled'])) {
                    unset($res[$k]);
                }
            }
            $ret = !empty($res) ? '{"result":"ok","Document":' . json_encode($res) . '}' : '{"result":"error"}';
        }
    }
    if ('PaymentList' == $callme) {
        if ($data['methodProperties']['Seller']) {
            $res = (new Server())->payment();
            foreach ($res as $k => $el) {
                if (isset($el['disabled'])) {
                    unset($res[$k]);
                }
            }
            $ret = !empty($res) ? '{"result":"ok","Document":' . json_encode($res) . '}' : '{"result":"error"}';
        }
    }
    if ('PaymentNotify' == $callme) {
        if ($data['methodProperties']['Document']) {
            $doc = new Server();
            $res = $doc->documentupdatesigns($data['methodProperties']);
            $ret = $res ? '{"result":"ok"}' : '{"result":"error"}';
        }
    }
}

//$lg->save($ret);
return $ret;
