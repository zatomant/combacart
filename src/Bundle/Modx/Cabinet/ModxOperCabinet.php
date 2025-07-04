<?php

namespace Comba\Bundle\Modx\Cabinet;

use Comba\Bundle\CombaApi\CombaApi;
use Comba\Bundle\Modx\ModxMarketplace;
use Comba\Bundle\Modx\ModxOper;
use function Comba\Functions\array_search_by_key;
use function Comba\Functions\filterArrayRecursive;

class ModxOperCabinet extends ModxOper
{

    public function setAction(): string
    {
        return 'cabinet';
    }

    public function render()
    {
        $docTpl = $this->getOptions('page') ? '@FILE:/cabinet_list' : '@FILE:/cabinet';

        $marketplace = new ModxMarketplace($this);

        $details = [
            'session' => $this->User()->getSession(),
            'name' => $this->User()->getName(),
            'fullname' => $this->User()->getName(),
            'id' => $this->User()->getId(),
            'email' => $this->User()->getEmail()
        ];

        $ret = json_decode(
            (new CombaApi())->request('CabinetRead',
                [
                    'User' => [
                        'id' => $details['id'],
                        'email' => $details['email'],
                        'session' => $details['session']
                    ],
                    'Marketplace' => $marketplace->get()['uid'],
                    'page' => $this->getOptions('page') ?? null
                ]
            ), true);

        $doc = $ret['result'] && $ret['result'] == 'ok' ? $ret['Document'] : [];

        $this->initLang();

        $mp_data = $marketplace->get();
        $mp_data = filterArrayRecursive($mp_data, null, ['uid', 'sellers']);

        $this->getModx()->tpl = \DLTemplate::getInstance($this->getModx());
        $_t = $this->getParser()
            ->getEngine()
            ->createTemplate($this->getChunk($docTpl));

        // виклик обробки шаблону для twig
        $_chunk = $_t->render(
            [
                'doclist' => $doc,
                'details' => $details,
                'marketplace' => $mp_data,
                'paging' => $ret['paging'] ?? null
            ]
        );

        // виклик обробки шаблону для modx
        return
            $this->getModx()
                ->tpl
                ->parseChunk($_chunk, $doc, true);

    }
}
