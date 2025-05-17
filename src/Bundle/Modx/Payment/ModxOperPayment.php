<?php

namespace Comba\Bundle\Modx\Payment;

use Comba;
use Comba\Bundle\Modx\ModxOper;
use Comba\Bundle\Modx\ModxOptions;
use Comba\Bundle\Modx\ModxMarketplace;
use Comba\Core\Entity;
use RemoteIP;
use function Comba\Functions\array_search_by_key;
use function Comba\Functions\safeHTML;

class ModxOperPayment extends ModxOper
{

    public function addPath(): ModxOperPayment
    {
        parent::addPath();
        $this->addPathLoader(Entity::get('PATH_ROOT') . DIRECTORY_SEPARATOR . Entity::get('PATH_TEMPLATES') . '/tabledata');
        return $this;
    }

    public function setAction(): string
    {
        return 'payment';
    }

    public function render()
    {

        $dataset = [];
        $uid = safeHTML($this->getOptions('uid'));
        $pagefull = $this->getOptions('pagefull') ? 'pagefull_' : '';
        $this->setTemplateFilename($pagefull . 'none');

        $marketplace = (new ModxMarketplace($this))->get();

        if (!empty($uid) && strlen($uid) > 8) {

            $ret = json_decode(
                (new ModxOptions($this))
                    ->setCachable()
                    ->request('Payment',
                        [
                            'Document' => [
                                'uid' => $uid,
                                'ip' => (new RemoteIP())->get_ip_address(),
                                'user' => $this->User() ? $this->User()->getId() : -1,
                                'user_name' => $this->User() ? $this->User()->getName() : ''
                            ]
                        ]
                    ),
                true);

            if ($ret['result'] == 'ok') {
                $dataset = $ret['Document'];
                if ($dataset['doc_type'] == 'doc_request') {
                    $this->setTemplateFilename($pagefull . 'payment');
                }
            }

            foreach ($dataset['payee']['pt'] as $p_id => $p_el) {
                if ($p_el['type'] == 'pt_online') {
                    try {
                        $this->log('INFO', "get3thAuth(" . $p_el['provider'] . ", " . $dataset['doc_seller'] . ")");
                        $p_auth = Entity::get3thAuth($p_el['provider'], $dataset['doc_seller']);
                        if (!empty($p_auth['class'])) {
                            $p_class = '\Comba\Bundle\Payment\Types\\' . $p_auth['class'] . '\\' . $p_auth['class'];
                            $dataset['payee']['pt'][$p_id]['ptcontent'] = (new $p_class($this))->setProvider($p_el['provider'], $p_auth)->getContent($dataset);
                        }
                    } catch (\Throwable $e) {
                        $this->log('CRITICAL', "Не визначено дані провайдера для класу \Payments" . array_search_by_key($p_auth, 'class') . ", документ $uid ");
                        continue;
                    }
                }
            }
        }

        return $this->renderParser(
            [
                'doc' => $dataset,
                'marketplace' => $marketplace ?? [],
                'pageprefix' => $pagefull
            ]
        );
    }

}
