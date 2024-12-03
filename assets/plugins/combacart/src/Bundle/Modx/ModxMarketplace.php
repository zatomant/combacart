<?php

namespace Comba\Bundle\Modx;

use function Comba\Functions\recursive_array_search_key_value;

class ModxMarketplace extends ModxOptions
{

    public function __construct($modx = null)
    {
        parent::__construct($modx);
        $this->isCachable = true;

        $auth = get3thAuth('Marketplace');
        $this->setUID($auth['uid']);
    }

    /** Повертає масив Продавців
     * @param string|null $uid
     * @return array
     */
    public function sellers(string $uid = null): array
    {
        $_sl = array();
        $el = $this->get();
        if ($uid){
            foreach ($el['sellers'] as $s){
                if ($s['uid'] == $uid){
                    $_sl[] = $s;
                }
            }
        } else {
            $_sl = $el['sellers'];
        }
        return $_sl;
    }

    public function get(): ?array
    {
        return $this->isExists('Document') ? $this->getOptions('Document') : $this->read();
    }

    private function read(): ?array
    {
        $this->delOptions('Document');
        $ret = json_decode($this->request('Marketplace', ['uid' => $this->getUID()]), true);
        if ($ret['result'] == 'ok') $this->set($ret['Document']);
        return $this->getOptions('Document');
    }

    private function set($value)
    {
        $this->setOptions('Document', $value);
    }

}
