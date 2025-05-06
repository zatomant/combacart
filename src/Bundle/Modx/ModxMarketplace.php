<?php

namespace Comba\Bundle\Modx;

use Comba\Core\Entity;
use DocumentParser;

class ModxMarketplace extends ModxOptions
{

    public function __construct(?object $parent = null, ?DocumentParser $modx = null)
    {
        parent::__construct($parent, $modx);
        $this->isCachable = true;

        $auth = Entity::getData('Marketplace');
        $this->setUID($auth['uid']);
    }

    /** Повертає масив Продавців
     * @param string|null $uid
     * @return array
     */
    public function sellers(string $uid = null): array
    {
        $_sl = [];
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
