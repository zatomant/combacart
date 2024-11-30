<?php

namespace Comba\Bundle\Modx\Tpl;

use Comba\Bundle\Modx\ModxOper;
use function Comba\Functions\safeHTML;

class ModxOperTpl extends ModxOper
{

    function addPath(): ModxOperTpl
    {
        parent::addPath();
        $this->addPathLoader(dirname(__FILE__) . '/templates');
        return $this->addPathLoader(COMBAMODX_PATH_ROOT . DIRECTORY_SEPARATOR . COMBAMODX_PATH_TEMPLATES . '/tabledata');
    }

    function setAction(): string
    {
        return 'tpl';
    }

    function render(array $dataset = null)
    {

        if (!defined('COMBA_MODE_S')) die;

        if (!empty($tpl = safeHTML($this->getOptions('tpl')))) {
            $this->setTemplateFilename($tpl . '.html');
        }

        return $this->renderParser(
            array(
                'doc' => $dataset,
            )
        );
    }

}
