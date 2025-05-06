<?php

namespace Comba\Bundle\Modx\Tpl;

use Comba\Bundle\Modx\ModxOper;
use Comba\Core\Entity;
use function Comba\Functions\safeHTML;

class ModxOperTpl extends ModxOper
{

    public function addPath(): ModxOperTpl
    {
        parent::addPath();
        $this->addPathLoader(Entity::get('PATH_ROOT') . DIRECTORY_SEPARATOR . Entity::get('PATH_TEMPLATES') . '/tabledata');
        return $this;
    }

    public function setAction(): string
    {
        return 'tpl';
    }

    public function render(array $dataset = null)
    {

        if (!defined('COMBA_MODE_S')) die;

        if (!empty($tpl = safeHTML($this->getOptions('tpl')))) {
            $this->setTemplateFilename($tpl);
        }

        return $this->renderParser(
            [
                'doc' => $dataset,
            ]
        );
    }

}
