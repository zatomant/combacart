<?php
/**
 * CombaHelper
 *
 * деякі функції для зручності використання
 *
 * @category    plugin
 * @version     2.5
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
use Comba\Bundle\BuildInServer\Manager;
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

include_once(dirname(__FILE__) . '/autoload.php');
include_once MODX_BASE_PATH . 'assets/lib/MODxAPI/modResource.php';

$tplExt = '';
$tplPath = '';

global $modx;
$e = $modx->event;

$modx->setPlaceholder('currency_name', 'UAH');
$modx->setPlaceholder('currency', 'грн');

if ($e->name == 'OnPageNotFound') {

    if (strpos($_SERVER['REQUEST_URI'], COMBAMODX_PAGE_COMBA) !== false) {
        echo (new Manager($modx))->render();
        exit;
    }
    if (strpos($_SERVER['REQUEST_URI'], 'trk?') !== false || strpos($_SERVER['REQUEST_URI'], COMBAMODX_PAGE_TRACKING . '?') !== false) {
        $_v = !empty($_GET['trk']) ? 'trk' : COMBAMODX_PAGE_TRACKING;
        $trk = !empty($_GET[$_v]) ? safeHTML($_GET[$_v]) : parse_url(safeHTML($_SERVER['REQUEST_URI']), PHP_URL_QUERY);
        if (!empty($trk)) {
            echo require_once(dirname(__FILE__) . '/snippetOrderTracking.php');
            exit;
        }
    }
    if (strpos($_SERVER['REQUEST_URI'], 'pay?') !== false || strpos($_SERVER['REQUEST_URI'], COMBAMODX_PAGE_PAYMENT . '?') !== false) {
        $_v = !empty($_GET['pay']) ? 'pay' : COMBAMODX_PAGE_PAYMENT;
        $pay = !empty($_GET[$_v]) ? safeHTML($_GET[$_v]) : parse_url(safeHTML($_SERVER['REQUEST_URI']), PHP_URL_QUERY);
        if (!empty($pay)) {
            echo require_once(dirname(__FILE__) . '/snippetOrderPay.php');
            exit;
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], COMBAMODX_PAGE_PAYMENT_CALLBACK . '?') !== false) {
        $requestUri = $_SERVER['REQUEST_URI'];
        $paymentstatus = $_POST;
        require_once(dirname(__FILE__) . '/snippetOrderPaymentCallback.php');
    }
    if (strpos($_SERVER['REQUEST_URI'], COMBAMODX_PAGE_TNX) !== false) {
        $params = [
            'action' => 'readrequest',
            'docTpl' => '@FILE:' . $tplPath . '/pagefull-CheckoutTnx' . $tplExt,
            'docEmptyTpl' => '@FILE:' . $tplPath . '/pagefull-CheckoutTnxEmpty' . $tplExt,
        ];
        echo $modx->runSnippet('CombaHelper', $params);

        $ch = new CombaHelper($modx);
        if ($ch->getCheckoutTnx()) {
            // видаляємо з кешу документів старий uid
            $ch->invalidateCache((new ModxUser($modx))->getSession());
        }
        exit;
    }
}

if ($e->name == 'OnDocFormSave') {
    (new CombaHelper($modx))->updateReferenceProduct($id);
}

if ($e->name == 'OnWebPageInit') {

    $action = isset($_POST['action']) ? $modx->stripTags($_POST['action']) : null;
    if (empty($action)) {
        return;
    }

    $goodsId = isset($_POST['goodsid']) ? (int)$modx->stripTags($_POST['goodsid']) : 0;
    $goodsGUID = isset($_POST['goodslguid']) ? $modx->stripTags($_POST['goodslguid']) : 0;
    $tvname = !empty($tvname) ? $tvname : GOODS_GOODS;

    if ($action == 'ch_insert') {

        if (empty($goodsId)) {
            exit;
        }

        $amount = isset($_POST['count']) ? (int)$modx->stripTags($_POST['count']) : 0;
        if (empty($amount) || $amount < 0 || $amount > COMBAMODX_GOODS_MAX_QUANTITY) {
            $amount = 1;
        }

        $ch = new CombaHelper($modx);
        if ($ch->isBot()) {
            exit;
        }

        $g2 = new ModxProduct();
        $g2->obtainFromModxObject($modx->getDocumentObject('id', $goodsId, 'all'))
            ->set($g2->filter('goods_md5', $goodsGUID));

        $cart = new ModxCart($modx);
        // create new cart if not exists
        if (empty($cart->getID())) {
            $cart->setOptions('id', $ch->create());
        }
        $cart->setOptions([
            'Product' => $g2->get(),
            'amount' => $amount
        ]);

        $ret = $cart->insert();
        if (!empty($ret) && $ret['result'] == 'ok') {
            $params = [
                'action' => 'read',
                'docTpl' => '@FILE:' . $tplPath . '/chunk-Cart' . $tplExt,
            ];
            echo $modx->runSnippet('CombaHelper', $params); // read cart specification
        }
        exit;
    }
    if ($action == 'ch_update') {

        $specid = isset($_POST['specid']) ? $modx->stripTags($_POST['specid']) : null;
        if ((new CombaHelper($modx))->isBot() || empty($specid)) {
            exit;
        }

        $amount = isset($_POST['count']) ? $modx->stripTags($_POST['count']) : 0;
        if (empty($amount) || $amount < 0 || $amount > COMBAMODX_GOODS_MAX_QUANTITY) {
            $amount = 1;
        }

        $ret = (new ModxCart($modx))
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
                'docTpl' => '@FILE:' . $tplPath . '/chunk-CheckoutSpec' . $tplExt,
                'docEmptyTpl' => '@FILE:' . $tplPath . '/chunk-CheckoutEmpty' . $tplExt,
            ];
            echo $modx->runSnippet('CombaHelper', $params); // read cart specification
        }
        exit;
    }
    if ($action == 'ch_delete') {

        $specid = isset($_POST['specid']) ? $modx->stripTags($_POST['specid']) : null;
        if ((new CombaHelper($modx))->isBot() || empty($specid)) {
            exit;
        }

        $ret = (new ModxCart($modx))
            ->setOptions('specid', $specid)
            ->delete();

        if (!empty($ret) && $ret['result'] == 'ok') {
            $params = [
                'action' => 'read',
                'docTpl' => '@FILE:' . $tplPath . '/chunk-CheckoutSpec' . $tplExt,
                'docEmptyTpl' => '@FILE:' . $tplPath . '/chunk-CheckoutEmpty' . $tplExt,
            ];
            echo $modx->runSnippet('CombaHelper', $params); // read cart specification
        }
        exit;
    }
    if ($action == 'ch_checkout') {

        $data = $_POST['formdata'] ?? '';

        $data = base64_decode($data);
        $data = rawurldecode($data);
        $obj = json_decode($data);

        $answer = new Answer('result_ok');
        $ch = new CombaHelper($modx);
        $ch->initLang();

        $captcha = $ch->captcha($obj->token ?? '');
        if (empty($captcha['error-codes']) && empty($captcha['confirm'])) {
            $modx->logEvent(1, 1, $ch->getIpAddr() . ' ' . json_encode($captcha) . 'INFO ' . json_encode($obj), 'Checkout captcha ' . $captcha['score'] . ' ' . $ch->getIpAddr());
        }
        /*
        if ($ch->isBot()) {
            $modx->logEvent(1, 1, json_encode($obj), 'CombaHelper Checkout Bot detected');
            $answer->setOptionsEx('Стався збій в обробці кошика з товарами. Оновіть сторінку та спробуйте ще раз.');
        }
        */

        if (!isset($obj->address) || !$ch->IsValidParam($obj->address, 8)) {
            if ($obj->typedelivery != 'dt_pickup') {
                $answer->setOptionsEx(array_search_by_key($ch->getLang(), 'prompt_delivery_to'))->setOptions('address', 'element');
            }
        }
        $obj->phone = preg_replace('~\D~', '', $obj->phone);
        if (!$ch->IsValidParam($obj->name, 4)) {
            $answer->setOptionsEx(array_search_by_key($ch->getLang(), 'prompt_customer'))->setOptions('name', 'element');
        }
        if (!$ch->IsValidParam($obj->phone, 8)) {
            $answer->setOptionsEx(array_search_by_key($ch->getLang(), 'prompt_customer_phone'))->setOptions('phone', 'element');
        }
        if ($ch->IsValidParam($obj->phone, 8) && strlen($obj->phone) > 17) {
            $answer->setOptionsEx(array_search_by_key($ch->getLang(), 'error_customer_phone'));
        }

        if ($answer->getOptions('status') == 'result_ok') {

            $obj->name_delivery = mb_strlen($obj->name_delivery > 1) ? $obj->name_delivery : $obj->name;
            $obj->phone_delivery = strlen($obj->phone_delivery > 1) ? $obj->phone_delivery : $obj->phone;

            $message = $obj->message;
            if (!empty($obj->telegram)) {
                $message = $message . ' Telegram: ' . $obj->telegram;
            }
            $message = $modx->stripTags($message);

            $ch->setOptions([
                'doc_client_name' => $modx->stripTags($obj->name),
                'doc_client_phone' => $modx->stripTags($obj->phone),
                'doc_client_email' => $modx->stripTags($obj->email),
                'doc_client_comment' => $message,
                'doc_client_address' => $modx->stripTags($obj->address),
                'doc_delivery' => $modx->stripTags($obj->typedelivery),
                'doc_delivery_client_name' => $modx->stripTags($obj->name_delivery),
                'doc_delivery_client_phone' => $modx->stripTags($obj->phone_delivery),
                'doc_payment' => $modx->stripTags($obj->typepayment),
                'doc_client_dncall' => $modx->stripTags($obj->option_dncall),
                'doc_client_usebonus' => $modx->stripTags($obj->option_usebonus)
            ]);

            $ret = $ch->checkOut();
            $curip = $ch->getIpAddr();

            if (!empty($ret)) {

                $marketplace = (new ModxMarketplace())->get();

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
                    //$modx->logEvent($goodsId, 2, 'Помилка в процедурі Checkout ' . $ch->getUID() . ', ip ' . $curip, 'CombaHelper');
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
                //$modx->logEvent($goodsId, 2, 'Помилка в процедурі Checkout ' . $ch->getUID() . ', ip ' . $curip, 'CombaHelper');
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
            //$modx->logEvent(1, 1, $answer->getOptions('message') . '<br>INFO ' . json_encode($obj), 'Checkout error');
        }
        echo $answer->serialize();
        exit;
    }
}
