<?php

namespace Comba\Bundle\Modx\Orderpay;

use Comba\Bundle\Modx\ModxOper;
use Comba\Bundle\Modx\ModxOptions;
use Comba\Bundle\Modx\ModxMarketplace;
use RemoteIP;
use function Comba\Functions\safeHTML;

class ModxOperOrderPay extends ModxOper
{

    public function addPath(): ModxOperOrderPay
    {
        parent::addPath();
        $this->addPathLoader(dirname(__FILE__) . '/templates');
        return $this->addPathLoader(COMBAMODX_PATH_ROOT . DIRECTORY_SEPARATOR . COMBAMODX_PATH_TEMPLATES . '/tabledata');
    }

    public function setAction(): string
    {
        return 'orderpay';
    }

    public function render()
    {

        $dataset = [];
        $uid = safeHTML($this->getOptions('pay'));
        $pagefull = $this->getOptions('pagefull') ? 'pagefull_' : '';
        $this->setTemplateFilename($pagefull . 'none.html');

        if (!empty($uid) && strlen($uid) > 8) {

            $ret = json_decode(
                (new ModxOptions($this->getModx()))
                    ->setCachable()
                    ->request('Payment',
                        array(
                            'Document' => [
                                'uid' => $uid,
                                'ip' => (new RemoteIP())->get_ip_address(),
                                'user' => $this->User() ? $this->User()->getId() : -1,
                                'user_name' => $this->User() ? $this->User()->getName() : ''
                            ]
                        )
                    ),
                true);

            if ($ret['result'] == 'ok') {
                $dataset = $ret['Document'];
                if ($dataset['doc_type'] == 'doc_request') {
                    $this->setTemplateFilename($pagefull . 'orderpay.html');
                }
            }
        }

        $marketplace = (new ModxMarketplace($this->getModx()))->get();

        $this->initLang();

        foreach ($dataset['payee']['pt'] as $p_id => $p_el) {
            if ($p_el['type'] == 'pt_online') {
                $p_auth = get3thAuth($p_el['provider']);
                $p_class = '\Comba\Bundle\Payments\\' . $p_auth['class'] . '\\' . $p_auth['class'];
                try {
                    $dataset['payee']['pt'][$p_id]['ptcontent'] = (new $p_class($this->getModx()))->setAuth($p_auth)->getContent($dataset);
                } catch (\Throwable $e){
                    continue;
                }
            }
        }

        return $this->renderParser([
                'doc' => $dataset,
                'marketplace' => $marketplace ?? [],
                'pageprefix' => $pagefull
            ]
        );
    }

}
