<?php

namespace Comba\Bundle\BuildInServer;

use Comba\Bundle\Modx\ModxMarketplace;
use Comba\Bundle\Modx\ModxOper;
use Comba\Bundle\Modx\ModxOptions;
use Comba\Bundle\Modx\ModxUser;
use Comba\Bundle\Modx\Tpl\ModxOperTpl;
use Comba\Bundle\Modx\Tracking\ModxOperTrackingExt;
use Comba\Core\Entity;
use Comba\Core\Parser;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use SMSClient;
use function Comba\Functions\recursive_array_search_key_value;
use function Comba\Functions\filterArrayRecursive;
use function Comba\Functions\sanitize;
use function Comba\Functions\sanitizeID;
use function Comba\Functions\array_search_by_key;

class Manager extends ModxOptions
{

    public function render(): ?string
    {

        define('COMBA_MODE_S', true);

        $userid = $_SESSION['mgrInternalKey'];
        if (!empty($userid)) {
            $mgr = $this->getModx()->getUserInfo($userid);
            $action = ($mgr['usertype'] == 'manager') ? 'orderlist' : 'restricted';

            if (Entity::MANAGER_SELLER_CHECK) {
                // якщо в налаштуавннях користувача, в полі comment, присутній перелік UID Продавців
                if (preg_match('/<seller>(.*?)<\/seller>/', $mgr['comment'], $matches)) {
                    $_sellers_bind = explode(';', $matches[1]);

                    // вважаемо що менеджер має доступ лише до цих Продавців
                    // тоді показуємо в аднімістративній частині замовлення лише від цих Продавців
                    if (!empty($_sellers_bind) || !empty(array_filter($_sellers_bind))) {
                        $this->setOptions('filter_sellers', $_sellers_bind);
                    }
                }
            }
        }

        if (empty($action)) {
            // якщо відсутня авторизація в адмінці - повертаємо 404 помилку
            $this->getModx()->sendErrorPage(true);
        } elseif ($action == 'restricted') {
            // якщо тип авторизованого користувача не 'manager' - повертаємо 403 помилку
            $this->getModx()->sendUnauthorizedPage(true);
        }

        return $this->renderAction();
    }

    private function renderAction(): string
    {

        $actionsMap = [
            Entity::PAGE_COMBA => 'readOrderList',
            'search' => 'readOrderList',
            'order' => 'readOrder',
            'saveorder' => 'saveOrder',
            'tracking' => 'tracking',
            'tpl' => 'readTpl',
            'print' => 'printTpl',
            'messenger' => 'messenger',
            'sendemail' => 'sendEmail',
            'sendsms' => 'sendSms'
        ];

        $out = '';
        $uri = preg_replace('#/+#', '/', getenv('REQUEST_URI'));
        preg_match('#(?:/([^/]+))?/' . Entity::PAGE_COMBA . '/([^/]+)/([^/]+)(?:/([^/]+))?#', $uri, $matches);
        $action = $matches[2] ?? '';
        $uid = $matches[3] ?? '';
        $args = $matches[4] ?? '';

        if (empty($action) && empty($uid)) {
            $action = Entity::PAGE_COMBA;
        }

        if (array_key_exists($action, $actionsMap)) {
            $functionName = $actionsMap[$action];
            if (method_exists($this, $functionName)) {
                $out = $this->$functionName([sanitizeID($uid), $args]);
            } else {
                $this->log("Функція $functionName не знайдена", LOG_WARNING);
            }
        } else {
            $this->log("Дія $action не підтримується", LOG_NOTICE);
        }
        return $out;
    }

    public function sendSms(array $params): string
    {
        $_msg_text = null;
        $_msg_type = 'warning';

        $data = $this->getFormData($_POST['formdata']);
        if (!empty($data)) {

            $phone = array_search_by_key($data, 'order_ntfn_delivery_client_phone');
            $subject = array_search_by_key($data, 'order_ntfn_sms_text');

            $phone = preg_replace("/\D/", '', $phone);

            if (empty($subject)) {
                $_msg_text = 'Порожне повідомлення не слід відправляти';
            }

            if (strlen($phone) >= 10 && strlen($phone) <= 12) {
                switch (strlen($phone)) {
                    case '10':
                        $phone = '+38' . $phone;
                        break;

                    case '12':
                        $phone = '+' . $phone;
                        break;
                }
            } else {
                $_msg_text = 'Перевірте чи вірно введено номер отримувача';
            }

            if (empty($_msg_text)) {

                $_doc = (new Server())->documentread($params[0]);

                $_sellers_bind = [];
                if (Entity::MANAGER_SELLER_CHECK) {
                    $_sellers_bind = $this->getOptions('filter_sellers');
                }

                if (!empty($_sellers_bind) && !in_array($_doc['Document']['doc_seller'], $_sellers_bind)) {
                    $auth = [];
                } else {
                    try {
                        $auth = Entity::get3thAuth('AlphaSMS', array_search_by_key($_doc, 'doc_seller'));
                    } catch (\Throwable $e){
                        $auth = [];
                    }
                }

                if (empty($auth)) {
                    $_msg_text = 'Відсутній провайдер API для цього продавця';
                } else {
                    $sms = new SMSClient($auth['login'], $auth['pass'], $auth['key']);
                    $id = $sms->sendSMS($auth['alias'], $phone, $subject);

                    if ($sms->hasErrors()) {
                        $str_er = $sms->getErrors();
                        $_msg_text = implode($str_er);
                        $this->log($str_er, LOG_ERR);
                    } else {
                        sleep(3);
                        $str = $sms->receiveSMS($id);
                        if ($sms->hasErrors() || $str == 'Помилка в номері.') {
                            $_msg_text = $str;
                            $this->log($str, LOG_ERR);
                        }
                    }
                }
            }
        }
        return json_encode([
            'result' => 'ok',
            'msg' => [
                'type' => $_msg_type,
                'text' => $_msg_text,
                'timeout' => 2000
            ]
        ]);
    }

    public function getFormData(string $data): array
    {
        parse_str(rawurldecode(base64_decode($data)), $obj);
        return $obj;
    }

    public function readOrder(array $params): string
    {
        $tpl = new ModxOperTpl(new Parser());
        $tpl->setModx($this->getModx());
        $tpl->addPathLoader(dirname(__FILE__) . '/templates');
        $tpl->setOptions('tpl', 'doc_request');

        $tpl->addGlobal('marketplace', (new ModxMarketplace())->get());
        $tpl->addGlobal('dlgID', $params[1]);

        $user = new ModxUser($this->getModx());
        $tpl->addGlobal('user',
            [
                'id' => $user->getId(),
                'name' => $user->getName()
            ]
        );

        $dataset = (new Server())->documentread($params[0]);
        $dataset['Document']['doc_seller_title'] = (new Server())->sellers($dataset['Document']['doc_seller'])['label'];

        $_sellers_bind = [];
        if (Entity::MANAGER_SELLER_CHECK) {
            $_sellers_bind = $this->getOptions('filter_sellers');
        }
        if (!empty($_sellers_bind) && !in_array($dataset['Document']['doc_seller'], $_sellers_bind)) {
            $tpl->setOptions('tpl', 'deny');
        } else {
            $dataset['Document']['typeofdelivery'] = (new Server())->delivery();
            $dataset['Document']['typeofpayment'] = (new Server())->payment();

            $_payee0 = (new Server())->marketplace($dataset['Document']['doc_marketplace'])['payee'] ?? null;
            $_payee1 = (new Server())->sellers($dataset['Document']['doc_seller'])['payee'] ?? $_payee0;

            $_p = [];
            foreach ($_payee1 as $k => $payee) {
                $_p[] = filterArrayRecursive((new Server())->payee($payee['uid']), ['uid', 'label'], ['pt']);
            }
            $dataset['Document']['typeofpayee'] = $_p;
        }
        return $tpl->render($dataset['Document']);
    }

    public function sendEmail(array $params): string
    {

        $_msg_text = null;
        $_msg_type = 'warning';

        $data = $this->getFormData($_POST['formdata']);
        if (!empty($data)) {

            $dataset = (new Server())->documentread($params[0]);

            $toemail = array_search_by_key($data, 'order_ntfn_delivery_client_email');
            $body = array_search_by_key($data, 'order_ntfn_email_text');

            if (!empty(array_search_by_key($data, 'order_ntfn_email_subject')) && strlen(array_search_by_key($data, 'order_ntfn_email_subject')) > 3) {
                $subject = array_search_by_key($data, 'order_ntfn_email_subject');
            } else {
                $subject = 'Інформація по замовленю №' . $dataset['Document']['doc_number'] . ' від ' . array_search_by_key((new Server())->sellers($dataset['Document']['doc_seller']), 'label');
            }

            $emailFrom = array_search_by_key((new Server())->marketplace($dataset['Document']['doc_marketplace']), 'email') ?? null;
            if (!empty(array_search_by_key($data, 'order_ntfn_email_mailfrom')) && strlen(array_search_by_key($data, 'order_ntfn_email_mailfrom')) > 3) {
                $emailFrom = sanitize(array_search_by_key($data, 'order_ntfn_email_mailfrom'));
            }

            $emailFromName = array_search_by_key((new Server())->marketplace($dataset['Document']['doc_marketplace']), 'label') ?? null;

            if (empty($toemail) || strlen($toemail) < 5) {
                $_msg_text = 'Заповніть електрону адресу отримувача!';
            }
            if (empty($emailFrom) || strlen($emailFrom) < 4) {
                $_msg_text = 'Відсутня електрона адреса відправника!';
            }

            if (empty($_msg_text)) {

                $transport = new SendmailTransport();
                $mailer = new Mailer($transport);

                try {
                    $email = (new Email())
                        ->from(new Address($emailFrom, $emailFromName))
                        ->to($toemail)
                        ->subject($subject)
                        ->html($body);

                    $mailer->send($email);
                } catch (TransportExceptionInterface|RfcComplianceException $e) {
                    $this->log($e->getMessage(), LOG_ERR);
                    $_msg_text = $e->getMessage();
                }

                if (empty($_msg_text)) {
                    $_msg_text = 'Повідомлення відправлено';
                    $_msg_type = 'info';
                }
            }
        }

        return json_encode([
            'result' => 'ok',
            'msg' => [
                'type' => $_msg_type,
                'text' => $_msg_text,
                'timeout' => 2000
            ]
        ]);
    }

    public function saveOrder(array $params): string
    {
        $uid = $params[0];
        $data = $this->getFormData($_POST['formdata']);

        $document = new Server();
        if (Entity::MANAGER_SELLER_CHECK) {
            $result = $document->setOptions('filter_sellers', $this->getOptions('filter_sellers'))->documentupdate($uid, $data);
        } else {
            $result = $document->documentupdate($uid, $data);
        }
        if (!$result && $document->getOptions('error_type') == 'deny') {
            $this->initLang();
            $ret = json_encode([
                'result' => 'ok',
                'msg' => [
                    'type' => 'error',
                    'text' => array_search_by_key($this->getLang(), 'error_action_canceled') . '<br>' . array_search_by_key($this->getLang(), 'error_deny_seller'),
                ]
            ]);
        } else {
            $ret = json_encode([
                'result' => 'ok',
                'msg' => [
                    'type' => 'element',
                    'element' => [
                        ['e' => 'tr[data-docuid="' . $uid . '"]', 'html' => $this->readOrderList([base64_encode(rawurlencode($uid)), 'one' => true]),],
                    ],
                ]
            ]);
        }
        // повертаемо дані для оновлення таблиці
        return $ret;
    }

    public function readOrderList($params): string
    {
        $tpl = new ModxOperTpl(new Parser());
        $tpl->setModx($this->getModx());
        $tpl->addPathLoader(dirname(__FILE__) . '/templates');
        $tpl->setOptions('tpl', 'documentlist');

        if (empty($params) || empty($params[0])) {
            $tpl->addGlobal('App',
                [
                    'name' => Entity::NAME,
                    'version' => Entity::VERSION,
                    'fver' => Entity::FILE_VER
                ]
            );
            $tpl->addGlobal('EVO', $this->getModx()->getVersionData());

            $user = new ModxUser($this->getModx());
            $tpl->addGlobal('user',
                [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'type' => $user->getOptions('type')
                ]
            );
            $tpl->addGlobal('languages', (new ModxOper())->setModx($this->getModx())->getLanguageList());
        }

        if (!empty($params) && !empty($params[0])) {

            $tpl->setOptions('tpl', 'table');
            if (!empty($params['one'])) {
                $tpl->setOptions('tpl', 'table.row');
            }

            $params[0] = base64_decode($params[0]);
            $params[0] = rawurldecode($params[0]);
            if (!empty($params[0]) && $params[0] != '-') {
                $params['search'] = $params[0];
            }
            if (!empty($params[1]) && is_numeric($params[1])) {
                $params['page'] = abs((int)$params[1]);
            }
        }

        $tpl->addGlobal('marketplace', (new ModxMarketplace())->get());

        if (Entity::MANAGER_SELLER_CHECK) {
            $dataset = (new Server())->setOptions('filter_sellers', $this->getOptions('filter_sellers'))->documentList($params);
        } else {
            $dataset = (new Server())->documentList($params);
        }

        return $tpl->render($dataset);
    }

    public function printTpl(array $params): string
    {
        return $this->readTpl($params, (new Server())->typeoftpl(['print']));
    }

    public function readTpl(array $params, array $tplform = null): string
    {
        $tplform = $tplform ?? (new Server())->typeoftpl();

        $parser = new ModxOperTpl(new Parser());
        $parser->setModx($this->getModx())
            ->addPathLoader(dirname(__FILE__) . '/templates')
            ->addPathLoader(dirname(__FILE__) . '/templates/print')
            ->addGlobal('marketplace', (new ModxMarketplace())->get());

        $dataset = array();
        $tpl = recursive_array_search_key_value($tplform, $params[1], 'name', false, 'file');
        if (empty($tpl)) {
            $tpl = 'file_not_found';
        } else {
            $dataset = (new Server())->documentread($params[0]);
            $dataset['Document']['seller'] = (new Server())->sellers($dataset['Document']['doc_seller']);
            $dataset['Document']['payee'] = (new Server())->payee($dataset['Document']['doc_payee']);
        }
        $parser->setOptions('tpl', $tpl);
        return $parser->render($dataset['Document'] ?? $dataset);
    }

    public function messenger(array $params): string
    {
        $parser = new ModxOperTpl(new Parser());
        $parser->setModx($this->getModx())
            ->addPathLoader(dirname(__FILE__) . '/templates')
            ->addPathLoader(dirname(__FILE__) . '/templates/message')
            ->setOptions('tpl', 'messenger')
            ->addGlobal('marketplace', (new ModxMarketplace())->get())
            ->addGlobal('dlgID', $params[0])
            ->addGlobal('typeoftpl', (new Server())->typeoftpl());

        $dataset = (new Server())->documentread($params[0]);
        $dataset['Document']['doc_seller_title'] = (new Server())->sellers($dataset['Document']['doc_seller'])['label'];
        $dataset['Document']['typeofdelivery'] = (new Server())->delivery();
        $dataset['Document']['typeofpayment'] = (new Server())->payment();

        return $parser->render($dataset['Document']);
    }

    public function tracking(array $params): string
    {
        return (new ModxOperTrackingExt(new Parser()))
            ->setModx($this->getModx())
            ->setOptions('trk', $params[0])
            ->render();
    }
}
