<?php

namespace Comba\Bundle\CombaHelper;

use Comba\Bundle\Modx\ModxCart;
use Comba\Bundle\Modx\ModxOptions;
use Comba\Bundle\Modx\ModxProduct;
use Comba\Bundle\Modx\ModxMarketplace;
use Comba\Bundle\Modx\ModxUser;
use Comba\Bundle\Modx\Tpl\ModxOperTpl;
use Comba\Core\Entity;
use Comba\Core\Options;
use Comba\Core\Parser;

use DocumentParser;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

use Symfony\Component\Mime\Exception\RfcComplianceException;
use function Comba\Functions\array_search_by_key;
use function Comba\Functions\safeHTML;
use function ctype_alpha;

use const MODX_BASE_PATH;

class CombaHelper extends ModxOptions
{
    private ModxUser $_user;

    public function __construct(?object $parent = null, ?DocumentParser $modx = null)
    {
        parent::__construct($parent, $modx);

        $this->_user = new ModxUser($this);
    }

    /** Call to reCaptcha and get check response array, has 'confirm' for bool
     * return null if captcha disabled
     * @param string $token
     * @return array
     */
    public function captcha(string $token): array
    {
        $auth = Entity::get3thAuth('reCaptcha', 'marketplace');
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

    /** Оформлення замовлення на сервері
     * повертає перелік UID замовлень або помиллку
     */
    public function checkOut()
    {
        if (empty($this->getUID())) {
            return 'result_error';
        }

        return json_decode($this->request('DocumentCheckout',
            [
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
            ]
        ), true);
    }

    /**
     * Повертає Document UID
     *
     * @return string|null
     */
    public function getUID(): ?string
    {
        return (new ModxCart($this))->getID();
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
        if (empty($contentid)) {
            return;
        }

        $modxObject = $this->getModx()->getDocumentObject('id', $contentid, 'all');
        $product = (new ModxProduct($this))->obtainFromModxObject($modxObject)->get();

        if (empty($product)) {
            return;
        }

        $this->request('ProductUpdateCartSpecUpdateSum',
            [
                'Product' => $product
            ]
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

        $dataset = (new ModxCart($this))
            ->setOptions('id', $this->getOptions('id'))
            ->get();

        if (empty($dataset)) {
            $this->log('CRITICAL', 'ERROR: empty dataset (' . $uid . ')');
            return 'result_error';
        }

        $marketplace = (new ModxMarketplace($this))->get();

        $marketplace_label = array_search_by_key($marketplace, 'label');

        $aTpl = (new ModxOperTpl())
            ->setModx($this->getModx())
            ->initLang()
            ->setOptions('tpl', $tpl)
            ->addGlobal('marketplace', $marketplace);

        $body = $aTpl->render($dataset);

        if (empty($body)) {
            $this->log('CRITICAL', 'ERROR: empty body (' . $tpl . ')');
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

                $from = array_search_by_key($marketplace, 'email');
                if (empty($from)){
                    continue;
                }

                try {
                    $sm = (new Email())
                        ->from(new Address($from, $marketplace_label))
                        ->to($email)
                        ->subject($subj)
                        ->html($body);

                    $isSent = true;
                    $transport = new SendmailTransport();
                    (new Mailer($transport))->send($sm);
                } catch (TransportExceptionInterface $e) {
                    $isSent = false;
                    $this->log('ERROR', $e->getMessage());
                } catch (RfcComplianceException $e){
                    $isSent = false;
                    $this->log('ERROR', $e->getMessage());
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
            [
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
            ]
        );
    }

    /** Return UID has created document
     * If Thanx page's time expired return false
     * @return null|string|array
     */
    public function getCheckoutTnx()
    {
        if (isset($_SESSION['ACTVT'])) {
            if (time() - $_SESSION['ACTVT'] > Entity::get('PAGE_TNX_TIMEOUT')) {
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

        $userenv = $this->getIpAddr();
        $userenv .= " " . htmlspecialchars($this->getAgent());

        $this->log('INFO', 'request DocumentNew');
        $_tmp = $this->ca->request('DocumentNew',
            [
                'Document' => [
                    'session' => $this->User()->getSession(),
                    'useruid' => $this->User()->getId(),
                    'username' => $this->User()->getName(),
                    'marketplace' => (new ModxMarketplace($this))->getUID(),
                    'userenv' => $userenv
                ]
            ]
        );

        $cart = json_decode($_tmp, true);
        if (!empty($cart['Document']['uid'])) {
            $this->log('INFO', 'created document ' . $cart['Document']['uid']);
            return $cart['Document']['uid'];
        } else {
            $this->log('CRITICAL', 'DocumentNew return empty');
        }

        return false;
    }

    /**
     * Set timeout and UIDs for Checkout Thanx page
     * @param $uid
     * @return void
     */
    public function setCheckoutTnx($uid)
    {
        $_SESSION['showtnx'] = $uid;
        $_SESSION['ACTVT'] = time();
    }

}
