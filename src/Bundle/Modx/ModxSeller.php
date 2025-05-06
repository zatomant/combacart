<?php

namespace Comba\Bundle\Modx;

use DocumentParser;

class ModxSeller extends ModxOptions
{
    public function __construct(?object $parent = null, ?DocumentParser $modx = null)
    {
        parent::__construct($parent, $modx);
        $this->isCachable = true;
    }

    public function get(): ?array
    {
        return $this->isExists('Document') ? $this->getOptions('Document') : $this->read();
    }

    private function read(): ?array
    {
        $this->delOptions('Document');
        $ret = json_decode(
            $this->request('Seller',
                [
                    'uid' => $this->getUID()
                ]
            ), true);

        if ($ret['result'] == 'ok') {
            $this->set($ret['Document']);
        }
        return $this->getOptions('Document');
    }

    private function set($value)
    {
        $this->setOptions('Document', $value);
    }
}
