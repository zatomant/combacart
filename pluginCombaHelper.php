<?php
/**
 * CombaHelper
 *
 * деякі функції для зручності використання
 *
 * @category    plugin
 * @version     2.6
 * @package     evo
 * @internal    @events OnDocFormSave,OnWebPageInit,OnPageNotFound,OnWebPagePrerender
 * @internal    @modx_category Comba
 * @author      zatomant
 * @lastupdate  22-02-2022
 */

use Comba\Bundle\CombaHelper\CombaHelper;
use Comba\Bundle\Modx\ModxCart;
use Comba\Bundle\Modx\ModxMarketplace;
use Comba\Bundle\Modx\ModxProduct;
use Comba\Bundle\Modx\ModxUser;
use Comba\Bundle\Modx\Payment\PaymentCallback;
use Comba\Bundle\Standalone\Manager;
use Comba\Core\Entity;
use Comba\Core\Answer;
use Comba\Core\Options;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

use function Comba\Functions\array_search_by_key;
use function Comba\Functions\safeHTML;

if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

require_once __DIR__ . '/autoload.php';

global $modx;
$e = $modx->event;

$_log_level = Entity::get('LOG_LEVEL');

$modx->setPlaceholder('currency_name', 'UAH');
$modx->setPlaceholder('currency', 'грн');

if ($e->name == 'OnPageNotFound') {

    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($requestUri, Entity::get('PAGE_COMBA')) !== false) {
        echo (new Manager(null, $modx))->render();
        exit;
    }

    $pageTracking = Entity::get('PAGE_TRACKING');
    if (strpos($requestUri, $pageTracking . '?') !== false) {
        $uid = !empty($_GET[$pageTracking]) ? safeHTML($_GET[$pageTracking]) : parse_url(safeHTML($requestUri), PHP_URL_QUERY);
        if (!empty($uid)) {
            echo $modx->runSnippet('CombaFunctions', ['fnct' => 'OrderTracking', 'uid' => $uid, 'pagefull' => 1]);
            exit;
        }
    }

    $pagePayment = Entity::get('PAGE_PAYMENT');
    if (strpos($requestUri, $pagePayment . '?') !== false) {
        $uid = !empty($_GET[$pagePayment]) ? safeHTML($_GET[$pagePayment]) : parse_url(safeHTML($requestUri), PHP_URL_QUERY);
        if (!empty($uid)) {
            echo $modx->runSnippet('CombaFunctions', ['fnct' => 'OrderPay', 'uid' => $uid, 'pagefull' => 1]);
            exit;
        }
    }

    $pageCallbackPayment = Entity::get('PAGE_PAYMENT_CALLBACK');
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($requestUri, $pageCallbackPayment . '?') !== false) {
        (new PaymentCallback(null, $modx))->render();
    }

    if (strpos($requestUri, Entity::get('PAGE_TNX')) !== false) {
        echo $modx->runSnippet('CombaFunctions', ['fnct' => 'CheckoutTnx', 'pagefull' => 1]);
        exit;
    }
}

if ($e->name == 'OnDocFormSave') {
    (new CombaHelper(null, $modx))
        ->setLogLevel($_log_level)
        ->updateReferenceProduct($id);
}

if ($e->name == 'OnWebPageInit') {

    (new \Comba\Bundle\Modx\ModxOper())->setModx($modx)->initLang();

    $action = isset($_POST['action']) ? $modx->stripTags($_POST['action']) : null;
    if (empty($action)) {
        return;
    }

    $goodsId = isset($_POST['goodsid']) ? (int)$modx->stripTags($_POST['goodsid']) : 0;
    $goodsGUID = isset($_POST['goodslguid']) ? $modx->stripTags($_POST['goodslguid']) : 0;
    $tvname = !empty($tvname) ? $tvname : Entity::get('TV_GOODS_GOODS');

    if ($action == 'ch_insert') {

        if (empty($goodsId)) {
            exit;
        }

        $amount = isset($_POST['count']) ? (int)$modx->stripTags($_POST['count']) : 0;
        if (empty($amount) || $amount < 0 || $amount > Entity::get('GOODS_MAX_QUANTITY')) {
            $amount = 1;
        }

        $ch = new CombaHelper(null, $modx);
        $ch->setLogLevel($_log_level);
        if ($ch->isBot()) {
            $ch->log('NOTICE', 'Клієнт не є людиною.');
            return;
        }

        $g2 = new ModxProduct(null, $modx);
        $g2->setLogLevel($_log_level)
            ->obtainFromModxObject($modx->getDocumentObject('id', $goodsId, 'all'))
            ->set($g2->filter('goods_md5', $goodsGUID));

        $cart = new ModxCart(null, $modx);
        $cart->setLogLevel($_log_level);
        if (empty($cart->getID())) {
            // Створити новий Кошик
            $cart->setOptions('id', $ch->create());
        }
        $cart->setOptions(
            [
                'Product' => $g2->get(),
                'amount' => $amount
            ]
        );

        $ret = $cart->insert();
        if (!empty($ret) && $ret['result'] == 'ok') {
            $params = [
                'action' => 'read',
                'docTpl' => '@FILE:/chunk_cart',
            ];
            echo $modx->runSnippet('CombaHelper', $params);
        }
        exit;
    }
    if ($action == 'ch_update') {

        $specid = isset($_POST['specid']) ? $modx->stripTags($_POST['specid']) : null;
        if (empty($specid)) {
            exit;
        }

        if ((new CombaHelper(null, $modx))->setLogLevel($_log_level)->isBot()) {
            return;
        }

        $amount = isset($_POST['count']) ? $modx->stripTags($_POST['count']) : 0;
        if (empty($amount) || $amount < 0 || $amount > Entity::get('GOODS_MAX_QUANTITY')) {
            $amount = 1;
        }

        $ret = (new ModxCart(null, $modx))
            ->setLogLevel($_log_level)
            ->setOptions(
                [
                    'specid' => $specid,
                    'amount' => $amount
                ]
            )
            ->update();

        if (!empty($ret) && $ret['result'] == 'ok') {
            $params = [
                'action' => 'read',
                'docTpl' => '@FILE:/chunk_checkout_spec',
                'docEmptyTpl' => '@FILE:/chunk_checkout_empty',
            ];
            echo $modx->runSnippet('CombaHelper', $params);
        }
        exit;
    }
    if ($action == 'ch_delete') {

        $specid = isset($_POST['specid']) ? $modx->stripTags($_POST['specid']) : null;
        if (empty($specid)) {
            exit;
        }
        if ((new CombaHelper(null, $modx))->setLogLevel($_log_level)->isBot()) {
            return;
        }

        $ret = (new ModxCart(null, $modx))
            ->setLogLevel($_log_level)
            ->setOptions('specid', $specid)
            ->delete();

        if (!empty($ret) && $ret['result'] == 'ok') {
            $params = [
                'action' => 'read',
                'docTpl' => '@FILE:/chunk_checkout_spec',
                'docEmptyTpl' => '@FILE:/chunk_checkout_empty',
            ];
            echo $modx->runSnippet('CombaHelper', $params);
        }
        exit;
    }
    if ($action == 'ch_checkout') {

        $data = $_POST['formdata'] ?? '';

        $data = base64_decode($data);
        $data = rawurldecode($data);
        $obj = json_decode($data);

        $answer = new Answer('result_ok');
        $ch = new CombaHelper(null, $modx);
        $ch->setLogLevel($_log_level)
            ->initLang();

        $captcha = $ch->captcha($obj->token ?? '');
        if (empty($captcha['error-codes']) && empty($captcha['confirm'])) {
            $ch->log(
                'CRITICAL',
                'Checkout captcha ' . $captcha['score'] . ' ' . $ch->getIpAddr() . "\n"
                . $ch->getIpAddr() . ' ' . json_encode($captcha) . 'INFO ' . json_encode($obj)
            );
        }
        /*
        if ($ch->isBot()) {
            $ch->log('CombaHelper Checkout Bot detected\n' . json_encode($obj), LOG_ERR);
            $answer->setOptionsEx('Стався збій в обробці кошика з товарами. Оновіть сторінку та спробуйте ще раз.');
        }
        */

        if (!isset($obj->address) || !$ch->IsValidParam($obj->address, 8)) {
            if ($obj->typedelivery && $obj->typedelivery != 'dt_pickup') {
                $answer->setOptionsEx(array_search_by_key($ch->getLang(), 'prompt_delivery_to'))->setOptions('address', 'element');
            }
        }
        $obj->phone = preg_replace('~\D~', '', $obj->phone ?? '');
        if (!$obj->name || !$ch->IsValidParam($obj->name, 4)) {
            $answer->setOptionsEx(array_search_by_key($ch->getLang(), 'prompt_customer'))->setOptions('name', 'element');
        }
        if (!$obj->phone || !$ch->IsValidParam($obj->phone, 8)) {
            $answer->setOptionsEx(array_search_by_key($ch->getLang(), 'prompt_customer_phone'))->setOptions('phone', 'element');
        }
        if (!$obj->phone || ($ch->IsValidParam($obj->phone, 8) && strlen($obj->phone) > 17)) {
            $answer->setOptionsEx(array_search_by_key($ch->getLang(), 'error_customer_phone'));
        }

        if ($answer->getOptions('status') == 'result_ok') {

            $obj->name_delivery = $obj->name_delivery && mb_strlen($obj->name_delivery > 1) ? $obj->name_delivery : $obj->name;
            $obj->phone_delivery = $obj->phone_delivery && strlen($obj->phone_delivery > 1) ? $obj->phone_delivery : $obj->phone;

            $message = $obj->message ?? '';
            if (!empty($obj->telegram)) {
                $message = $message . ' Telegram: ' . $obj->telegram;
            }
            $message = $modx->stripTags($message);

            $ch->setOptions(
                [
                    'doc_client_name' => $modx->stripTags($obj->name),
                    'doc_client_phone' => $modx->stripTags($obj->phone),
                    'doc_client_email' => $modx->stripTags($obj->email),
                    'doc_client_comment' => $message,
                    'doc_client_address' => $modx->stripTags($obj->address),
                    'doc_delivery' => $modx->stripTags($obj->typedelivery),
                    'doc_delivery_client_name' => $modx->stripTags($obj->name_delivery),
                    'doc_delivery_client_phone' => $modx->stripTags($obj->phone_delivery),
                    'doc_payment' => $modx->stripTags($obj->typepayment ?? 'pt_cashless'),
                    'doc_client_dncall' => $modx->stripTags($obj->option_dncall ?? 1),
                    'doc_client_usebonus' => $modx->stripTags($obj->option_usebonus ?? 0)
                ]
            );

            $ret = $ch->checkOut();
            $curip = $ch->getIpAddr();

            if (!empty($ret)) {

                $marketplace = (new ModxMarketplace())->setLogLevel($_log_level)->get();

                if ($ret['result'] == 'ok' && !empty($ret['Document']['uid'])) {

                    $ch->invalidateCache($ch->getUID());

                    $uids = $ret['Document']['uid'];
                    $ch->setCheckoutTnx($uids);

                    // формування та відправлення email
                    // по кожному замовленю
                    foreach ($uids as $uid_new) {
                        $ch->notify(
                            (new Options())
                                ->setOptions(
                                    [
                                        'uid' => $uid_new,
                                        'type' => 'event_ip',
                                        'status' => '',
                                        'subject' => 'IP',
                                        'header' => 'IP клієнта',
                                        'body' => $curip,
                                        'user' => $ch->User()->getId(),
                                        'user_name' => $ch->User()->getName()
                                    ]
                                ));

                        // відправлення листа замовнику
                        $ch->setOptions([
                            'id' => $uid_new,
                            'uid' => $uid_new,
                            'tpl' => 'etpl_34',
                            'bnoty' => '1'
                        ])->sendEmailTo();

                        // відправлення листа менеджеру
                        $ch->setOptions([
                            'bnoty' => '0',
                            'tpl' => 'etpl_35',
                            'toEmail' => array($marketplace['email'], $marketplace['emailinfo'])
                        ])->sendEmailTo();
                    }

                } else {

                    $sm = (new Email())
                        ->from(new Address($marketplace['emailsupport'], $marketplace['emailsupport']))
                        ->to($marketplace['emailsupport'])
                        ->subject('Помилка в процедурі Checkout ' . $ch->getUID() . ' ' . $marketplace['emailsupport'])
                        ->html('Перевірте лог, ip ' . $curip . ', doc ' . $ch->getUID());

                    $isSent = true;
                    $transport = new SendmailTransport();
                    try {
                        (new Mailer($transport))->send($sm);
                    } catch (TransportExceptionInterface $e) {
                        $isSent = false;
                        $this->log($e->getMessage(), LOG_ERR);
                    }

                    $answer->setOptionsEx(array_search_by_key($ch->getLang(), 'error_alert'));
                    $ch->log('ERROR', 'Помилка в процедурі Checkout ' . $ch->getUID() . ', ip ' . $curip);
                }
            } else {
                $ch->notify(
                    (new Options())
                        ->setOptions(
                            [
                                'uid' => $ch->getUID(),
                                'type' => 'event_system_message',
                                'status' => '',
                                'subject' => 'Помилка в кошику',
                                'header' => 'Помилка в кошику',
                                'body' => 'Порожня відповідь від сервера в процедурі DocumentCheckout',
                                'user' => $ch->User()->getId(),
                                'user_name' => $ch->User()->getName()
                            ]
                        ));

                $answer->setOptionsEx(array_search_by_key($ch->getLang(), 'error_alert'));
                $ch->log('CRITICAL', 'Помилка в процедурі Checkout ' . $ch->getUID() . ', ip ' . $curip);
            }
        } else {
            $ch->notify(
                (new Options())
                    ->setOptions(
                        [
                            'uid' => $ch->getUID(),
                            'type' => 'event_system_message',
                            'status' => '',
                            'subject' => 'Помилка в кошику',
                            'header' => 'Помилка в кошику',
                            'body' => $answer->getOptions('message') . (!empty($answer->getOptions('field')) ? " (" . $answer->getOptions('field') . ")" : ''),
                            'user' => $ch->User()->getId(),
                            'user_name' => $ch->User()->getName()
                        ]
                    ));
            $ch->log('CRITICAL', 'Checkout error\n' . $answer->getOptions('message') . '<br>INFO ' . json_encode($obj));
        }
        echo $answer->serialize();
        exit;
    }
    if ($action == 'ch_cabinet') {

        $currentPage = isset($_POST['page']) ? (int)$modx->stripTags($_POST['page']) : null;
        if (empty($currentPage)) {
            exit;
        }

        $params = [
            'fnct' => 'cabinet',
            'page' => $currentPage
        ];
        echo $modx->runSnippet('CombaFunctions', $params);
        exit;
    }
}
