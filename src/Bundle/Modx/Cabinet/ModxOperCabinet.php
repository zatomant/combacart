<?php

namespace Comba\Bundle\Modx\Cabinet;

use Comba\Bundle\CombaApi\CombaApi;
use Comba\Bundle\CombaHelper\CombaHelper;
use Comba\Bundle\Modx\ModxMarketplace;
use Comba\Bundle\Modx\ModxOper;
use function Comba\Functions\array_search_by_key;
use function Comba\Functions\filterArrayRecursive;

//require_once MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php';

class ModxOperCabinet extends ModxOper
{

    public function addPath(): ModxOperCabinet
    {
        return $this->addPathLoader(dirname(__FILE__) . '/templates');
    }

    public function setAction(): string
    {
        return 'cabinet';
    }

    public function render()
    {
        $docTpl = '@FILE:/cabinet';

        $ch = new CombaHelper($this->getModx());
        $ch->setTemplatesPath(str_replace(getenv('DOCUMENT_ROOT'), '', dirname(__FILE__)) . '/templates/');

        $ca = new CombaApi();
        $ret = json_decode($ca->request('CabinetRead',
            [
                'User' => [
                    'useruid' => array_search_by_key($this->getOptions('details'), 'id'),
                    'useremail' => array_search_by_key($this->getOptions('details'), 'email'),
                    'usersession' => array_search_by_key($this->getOptions('details'), 'session'),
                ],
                'Marketplace' => (new ModxMarketplace())->get()['uid']
            ]
        ), true);

        $doc = ($ret['result'] == 'ok') ? $ret['Document'] : array();

        $this->initLang();

        $marketplace = (new ModxMarketplace())->get();
        $marketplace = filterArrayRecursive($marketplace, null, ['uid', 'sellers']);

        $this->getModx()->tpl = \DLTemplate::getInstance($this->getModx());
        $_t = $this->getParser()->getEngine()->createTemplate($this->getModx()->tpl->parseChunk($ch->getChunk($docTpl), $doc, true));

        return $this->getModx()->tpl->parseChunk('@CODE:' . $_t->render(
                [
                    'doclist' => $doc,
                    'details' => $this->getOptions('details'),
                    'marketplace' => $marketplace,
                ]
            ), $doc, true);

    }
}
