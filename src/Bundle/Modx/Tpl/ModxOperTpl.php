<?php

namespace Comba\Bundle\Modx\Tpl;

use Comba\Bundle\Modx\ModxOper;
use Comba\Core\Entity;
use function Comba\Functions\safeHTML;

class ModxOperTpl extends ModxOper
{

    function addPath(): ModxOperTpl
    {
        parent::addPath();
        $this->addPathLoader(dirname(__FILE__) . '/templates');
        return $this->addPathLoader(Entity::PATH_ROOT . DIRECTORY_SEPARATOR . Entity::PATH_TEMPLATES . '/tabledata');
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
