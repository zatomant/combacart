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

        $ret = json_decode(
            (new CombaApi())->request('CabinetRead',
                [
                    'User' => [
                        'id' => array_search_by_key($this->getOptions('details'), 'id'),
                        'email' => array_search_by_key($this->getOptions('details'), 'email'),
                        'session' => array_search_by_key($this->getOptions('details'), 'session'),
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
            ->createTemplate(
                $this->getModx()
                    ->tpl
                    ->parseChunk($this->getChunk($docTpl), $doc, true)
            );

        return $this->getModx()->tpl->parseChunk('@CODE:' .
            $_t->render(
                [
                    'doclist' => $doc,
                    'details' => $this->getOptions('details'),
                    'marketplace' => $mp_data,
                    'paging' => $ret['paging'] ?? null
                ]
            ),
            $doc, true);

    }
}
