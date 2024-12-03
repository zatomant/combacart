<?php

namespace Comba\Bundle\Modx;

class ModxSeller extends ModxOptions
{

    public function __construct()
    {
        parent::__construct();
        $this->isCachable = true;
    }

    public function get(): ?array
    {
        return $this->isExists('Document') ? $this->getOptions('Document') : $this->read();
    }

    private function read(): ?array
    {
        $this->delOptions('Document');
        $ret = json_decode($this->request('Seller', ['uid' => $this->getUID()]), true);
        if ($ret['result'] == 'ok') $this->set($ret['Document']);
        return $this->getOptions('Document');
    }

    private function set($value)
    {
        $this->setOptions('Document', $value);
    }
}
