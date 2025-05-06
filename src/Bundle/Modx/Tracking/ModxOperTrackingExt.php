<?php

namespace Comba\Bundle\Modx\Tracking;

use Comba\Bundle\Modx\ModxOper;
use Comba\Bundle\Modx\ModxOptions;
use Comba\Bundle\Modx\ModxMarketplace;
use Comba\Core\Entity;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RemoteIP;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

use function Comba\Functions\array_search_by_key;
use function Comba\Functions\safeHTML;

class ModxOperTrackingExt extends ModxOper
{

    public function addPath(): ModxOperTrackingExt
    {
        parent::addPath();
        $this->addPathLoader(Entity::get('PATH_ROOT') . DIRECTORY_SEPARATOR . Entity::get('PATH_TEMPLATES') . '/tabledata');
        return $this;
    }

    public function setAction(): string
    {
        return 'trackingext';
    }

    public function render()
    {
        $uid = safeHTML($this->getOptions('trk'));

        $pagefull = $this->getOptions('pagefull') ? 'pagefull_' : '';
        $this->setTemplateFilename($pagefull . 'none');

        $dataset = [];
        if (!empty($uid) && strlen($uid) > 8) {

            $ret = json_decode(
                (new ModxOptions($this))
                    ->setCachable()
                    ->request('Tracking',
                        [
                            'Document' => array(
                                'uid' => $uid,
                                'ip' => (new RemoteIP())->get_ip_address(),
                                'user' => $this->User() ? $this->User()->getId() : -1,
                                'user_name' => $this->User() ? $this->User()->getName() : ''
                            )
                        ]
                    ),
                true);

            if ($ret['result'] == 'ok') {
                $dataset = $ret['Document'];
            }

            if (!empty($dataset)) {
                if (strlen($dataset['doc_delivery_number']) > 4) {
                    $bcinfo = $this->getBarcodeInfo($dataset['doc_delivery'], $dataset['doc_delivery_number'], $dataset['doc_marketplace'], $dataset['doc_seller']);
                    if (!empty($bcinfo)) {
                        $dataset = array_merge($dataset, $bcinfo);
                    }
                }
                $this->setTemplateFilename($pagefull . 'tracking');
            }
        }

        $marketplace = (new ModxMarketplace($this))->get();
        $this->initLang();

        return $this->renderParser(
            [
                'doc' => $dataset,
                'marketplace' => $marketplace ?? []
            ]
        );
    }

    public function getBarcodeInfo(string $dt, string $bc, string $marketplace_uid, string $seller_uid): ?array
    {
        if (empty($dt) || empty($bc)) {
            return null;
        }

        $operAr = $this->getActionList(__DIR__ . '/Types');

        $class = !empty($operAr[$dt]) ? $operAr[$dt] : null;
        if (empty($class)) {
            return null;
        }

        if (!class_exists($class)) {
            return [];
        }

        $bci = new $class;
        $ret = $bci->getBarcodeInfo($bc, $seller_uid);

        if (!empty($bci->getLastError())) {
            $shop = new ModxMarketplace($this);
            $elem = $shop->setUID($marketplace_uid)->get();

            $_body = "Server : " . getenv('SERVER_ADDR') . " " . Entity::get('SERVER_NAME') . "<br>";
            $_body .= 'Tracking class ' . $class . '<br>';
            $_body .= 'Tracking number ' . $bc . '<br>';
            $_body .= !empty($bci->getLastError(true)) ? $bci->getLastError(true) : ' Error procedure tracking';

            $this->log('ERROR', 'Tracking procedure ' . $_body);

            $sm = (new Email())
                ->from(new Address(array_search_by_key($elem, 'emailsupport'), array_search_by_key($elem, 'label')))
                ->to(array_search_by_key($elem, 'emailsupport'))
                ->subject($class)
                ->html($_body);

            $transport = new SendmailTransport();
            $mailer = new Mailer($transport);

            try {
                $mailer->send($sm);
            } catch (TransportExceptionInterface $e) {
            }
        }

        return [
            'trk_url' => $bci->getUrl() . $bc,
            'trk_title' => $bci->GetTitle(),
            'trk_urltracking' => $bci->getUrlTracking() . $bc,
            'trackingservice' => $ret
        ];
    }

    /**
     * Get action list each class file
     *
     * @param string $path path to files
     *
     * @return array
     */
    public function getActionList(string $path): array
    {
        $files = [];
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($rii as $file) {

            if ($file->isDir()) {
                continue;
            }
            if (strpos($file->getBasename(), 'Tracking') === false) {
                continue;
            }
            if (strpos($file->getBasename(), 'TrackingNone') !== false) {
                continue;
            }

            //$class = $file->getBasename('.php');
            $class = 'Comba\\Bundle' . str_replace('/', '\\', explode("Bundle", $file->getPath())[1]) . '\\' . $file->getBasename('.php');
            $action = new $class();
            $files = array_merge($action->getType(), $files);
        }
        return $files;
    }
}

