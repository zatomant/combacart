<?php

namespace Comba\Bundle\CombaHelper;

use Comba\Bundle\Modx\ModxCart;
use Comba\Bundle\Modx\ModxOptions;
use Comba\Bundle\Modx\ModxProduct;
use Comba\Bundle\Modx\ModxMarketplace;
use Comba\Bundle\Modx\ModxUser;
use Comba\Bundle\Modx\Tpl\ModxOperTpl;
use Comba\Core\Options;
use Comba\Core\Parser;
use RemoteIP;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

use function Comba\Functions\array_search_by_key;
use function Comba\Functions\safeHTML;
use function ctype_alpha;
use function get3thAuth;

use const MODX_BASE_PATH;

class CombaHelper extends ModxOptions
{
    public string $templatExtension = '.html.twig';
    public string $templatesPath;
    private ModxUser $_user;

    public function __construct($modx)
    {
        parent::__construct($modx);
        $this->setTemplatesPath(str_replace(getenv('DOCUMENT_ROOT'), '', dirname(__FILE__)) . '/templates/');

        $this->_user = new ModxUser($this->getModx());
        $this->_user->setLogLevel($this->getLogLevel());
    }

    public function setTemplatesPath(string $path)
    {
        $this->templatesPath = $path;
    }

    /** Call to reCaptcha and get check response array, has 'confirm' for bool
     * return null if captcha disabled
     * @param string $token
     * @return array
     */
    public function captcha(string $token): array
    {
        $auth = get3thAuth('reCaptcha');
        if (empty($auth['secret']) || empty($auth['url'])) {
            return ['error-codes' => 'missing-input-secret'];
        }

        $params = [
            'secret' => $auth['secret'],
            'response' => $token,
            'remoteip' => getenv('REMOTE_ADDR')
        ];

        $ch = curl_init($auth['url']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response_raw = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response_raw, true);
        if (!empty($response['success']) && !empty($response['score'])) {
            $response['confirm'] = ($response['score'] > 0.5);
        } else {
            $response['confirm'] = false;
        }
        return $response;
    }

    /** оформлення замовлення на сервері
     * повертає перелік UID замовлень або помиллку
     */
    public function checkOut()
    {
        if (empty($this->getUID())) {
            return 'result_error';
        }

        return json_decode($this->request('DocumentCheckout',
            array(
                'Document' => [
                    'uid' => $this->getUID(),
                    'doc_client_name' => $this->getOptions('doc_client_name'),
                    'doc_client_phone' => $this->getOptions('doc_client_phone'),
                    'doc_client_email' => $this->getOptions('doc_client_email'),
                    'doc_client_comment' => $this->getOptions('doc_client_comment'),
                    'doc_client_address' => $this->getOptions('doc_client_address'),
                    'doc_delivery' => $this->getOptions('doc_delivery'),
                    'doc_delivery_client_name' => $this->getOptions('doc_delivery_client_name'),
                    'doc_delivery_client_phone' => $this->getOptions('doc_delivery_client_phone'),
                    'doc_payment' => $this->getOptions('doc_payment'),
                    'doc_client_dncall' => $this->IsSign($this->getOptions('doc_client_dncall')),
                    'doc_client_usebonus' => $this->IsSign($this->getOptions('doc_client_usebonus')),
                    'doc_muser' => $this->User()->getId(),
                ]
            )
        ), true);
    }

    /**
     * Повертає Document UID
     *
     * @return string|null
     */
    public function getUID(): ?string
    {
        return (new ModxCart($this->getModx()))->getID();
    }

    /**
     * Перевірка статусу чекбокса
     *
     * @param $value
     * @return int
     */
    public function isSign($value): int
    {
        return ($value == 'on') ? 1 : 0;
    }

    /**
     * Get CombaModxUser class
     * @return ModxUser
     */
    public function User(): ModxUser
    {
        return $this->_user;
    }

    /** Update product data on Comba server
     *  and update customer`s cart with this product
     * @param int $contentid
     * @return void
     */
    public function updateReferenceProduct(int $contentid)
    {
        if (empty($contentid)) return;

        $modxObject = $this->getModx()->getDocumentObject('id', $contentid, 'all');
        $product = (new ModxProduct())->obtainFromModxObject($modxObject)->get();
        if (empty($product)) return;

        $this->request('ProductUpdateCartSpecUpdateSum',
            array(
                'Product' => $product
            )
        );
    }

    public function isValidParam($name, $minlen): bool
    {
        return !(strlen($name) < $minlen);
    }

    /**
     * @return string|void
     */
    public function sendEmailTo()
    {
        $uid = $this->getUID();
        if (empty($uid)) {
            return 'result_error';
        }

        define('COMBA_MODE_S', true);

        $tpl = $this->getOptions('tpl');
        $toEmail = $this->getOptions('toEmail');

        $dataset = (new ModxCart($this->getModx()))
            ->setOptions('id', $this->getOptions('id'))
            ->get();

        if (empty($dataset)) {
            $this->log('ERROR: empty dataset (' . $uid . ')', LOG_ERR);
            return 'result_error';
        }

        $marketplace = (new ModxMarketplace())->get();

        $marketplace_label = array_search_by_key($marketplace, 'label');

        $aTpl = (new ModxOperTpl(new Parser()))
            ->setModx($this->getModx())
            ->initLang()
            ->setOptions('tpl', $tpl)
            ->addGlobal('marketplace', $marketplace);

        $body = $aTpl->render($dataset);

        if (empty($body)) {
            $this->log('ERROR: empty body (' . $tpl . ')', LOG_ERR);
            return 'result_error';
        }

        $subj = $aTpl->lang['request'] . ' ' . $aTpl->lang['num'] . $dataset['doc_number'] . ' | ' . $marketplace_label;

        if (!is_array($toEmail)) {
            if (empty($toEmail) || strlen($toEmail) < 5) {
                $toEmail = $dataset['doc_client_email'];
            } else {
                $subj = $aTpl->lang['request'] . ' ' . $aTpl->lang['num'] . $dataset['doc_number'] . ' ' . $aTpl->lang[$dataset['doc_delivery']] . ' | ' . $marketplace_label;
            }
            $arEmail = array($toEmail);
        } else {
            $arEmail = array_slice($toEmail, 0);
            $subj = $aTpl->lang['request'] . ' ' . $aTpl->lang['num'] . $dataset['doc_number'] . ' ' . $aTpl->lang[$dataset['doc_delivery']] . ' | ' . $marketplace_label;
        }

        foreach ($arEmail as $email) {
            if (!empty($email) && strlen($email) > 5) {

                $sm = (new Email())
                    ->from(new Address(array_search_by_key($marketplace, 'email'), $marketplace_label))
                    ->to($email)
                    ->subject($subj)
                    ->html($body);

                $isSent = true;
                $transport = new SendmailTransport();
                try {
                    (new Mailer($transport))->send($sm);
                } catch (TransportExceptionInterface $e) {
                    $isSent = false;
                    $this->log($e->getMessage(), LOG_ERR);
                }

                if ($isSent && !empty($this->getOptions('bnoty'))) {

                    $this->notify(
                        (new Options())
                            ->setOptions([
                                'uid' => $uid,
                                'header' => $email,
                                'status' => 'Повідомлення відправлено',
                                'user' => $this->User()->getId(),
                                'user_name' => $this->User()->getName(),
                                'type' => 'event_email',
                                //'type' => '__byName',
                                'subject' => '__byName',
                                'body' => '__byName',
                                '__byName' => safeHTML($tpl)
                            ])
                    );

                }
            }
        }
    }

    /** Формує та відправляє сповіщення та сервер
     * @return false|mixed
     */
    public function notify(Options $ntfOpt = null)
    {
        $ntf = $ntfOpt ?: $this;
        if (empty($ntf->getOptions('uid'))) {
            return 'result_error';
        }

        return $this->ca->request('NotifyInsert',
            array(
                'Document' => [
                    'uid' => $ntf->getOptions('uid'),
                    'body' => $ntf->getOptions('body'),
                    'header' => $ntf->getOptions('header'),
                    'status' => $ntf->getOptions('status'),
                    'subject' => $ntf->getOptions('subject'),
                    'type' => $ntf->getOptions('type'),
                    'user' => $ntf->getOptions('user'),
                    'user_name' => $ntf->getOptions('user_name'),
                    '__byName' => $ntf->getOptions('__byName'),
                ]
            )
        );
    }

    /** Return UID has created document
     * If Thanx page's time expired return false
     * @return null|string|array
     */
    public function getCheckoutTnx()
    {
        if (isset($_SESSION['ACTVT'])) {
            if (time() - $_SESSION['ACTVT'] > COMBAMODX_PAGE_TNX_TIMEOUT) {
                unset($_SESSION['showtnx']);
                unset($_SESSION['ACTVT']);
            } else {
                if (!empty($_SESSION['showtnx'])) {
                    return $_SESSION['showtnx'];
                }
            }
        }
        return null;
    }

    /**
     * Create new UUID for Cart and set user`s evn
     * @return string|null
     */
    public function create(): ?string
    {
        $this->User()->prepareUserEnv();
        if ($this->getOptions('readOnly') == 1 || empty($this->User()->getSession())) {
            return null;
        }

        $browser = array();
        $userenv = $this->getIpAddr();
        if (getenv('HTTP_USER_AGENT') !== null) {
            $browser = get_browser(null, true);
        }

        if (!empty($browser)) {
            $userenv .= " " . $browser['browser'];
            $userenv .= " " . $browser['version'];
            $userenv .= ", " . $browser['platform'];
        }

        $this->log('create document', LOG_NOTICE);
        $_tmp = $this->ca->request('DocumentNew',
            array(
                'Document' => [
                    'session' => $this->User()->getSession(),
                    'useruid' => $this->User()->getId(),
                    'username' => $this->User()->getName(),
                    'marketplace' => (new ModxMarketplace())->getUID(),
                    'userenv' => $userenv
                ]
            ));

        $cart = json_decode($_tmp, true);
        return !empty($cart['Document']['uid']) ? $cart['Document']['uid'] : false;
    }

    public function getIpAddr(): string
    {
        $ip = (new RemoteIP())->get_ip_address();
        return empty($ip) || strlen($ip) < 8 ?? 'no-ip';
    }

    /**
     * Set timeout and UIDs for Checkout Thanx page
     * @param $uid ids of created order
     * @return void
     */
    public function setCheckoutTnx($uid)
    {
        $_SESSION['showtnx'] = $uid;
        $_SESSION['ACTVT'] = time();
    }

    /**
     * Шукає та повертає evo|modx чанк
     * для підтримки старого коду
     */
    public function getChunk($tpl, $bRecurse = false)
    {
        $template = $tpl;
        if (substr($tpl, 0, 6) == '@FILE:') {
            $tpl = $this->prepareFilename($tpl);
            $_tpl_file = MODX_BASE_PATH . substr($tpl, 6);
            if (file_exists($_tpl_file)) {
                $template = file_get_contents($_tpl_file);
            }

            preg_match("!<include>(.*?)</include>!si", $template, $inc);
            if ($inc) {
                foreach ($inc as $el) {
                    $str = $this->getChunk($el, true);
                    $template = str_replace('<include>' . $el . '</include>', $str, $template);
                }
            }
        }
        if (!$bRecurse) {
            $template = '@CODE:' . $template;
        }
        return $template;
    }

    /** Повертає ім'я файлу враховуючи префікс мови в uri
     * @param string $tpl
     * @return string|void
     */
    public function prepareFilename(string $tpl)
    {
        if (empty($tpl)) {
            return;
        }

        if (substr($tpl, 6, 1) === '/') {
            $tpl = str_replace('/', $this->templatesPath, $tpl);
        }
        $lang = $this->detectLanguage();
        if (!empty($lang) && ctype_alpha($lang)) {
            $_tpl = $tpl . '_' . $lang;
            if (file_exists(substr($_tpl . $this->templatExtension, 6))) {
                $tpl = $_tpl;
            }
        }
        return $tpl . $this->templatExtension;
    }

}
