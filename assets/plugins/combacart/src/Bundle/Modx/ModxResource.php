<?php

namespace Comba\Bundle\Modx;

use Comba\Core\Logs;

class ModxResource
{
    private $_modx;

    public function __construct($modx = null)
    {
        $this->setModx($modx);

        $this->_log_filename = get_class($this);
        $this->_log = new Logs($this->_log_filename);
    }

    public function clearCache(string $type = 'full'): ModxResource
    {
        $this->getModx()->clearCache($type);
        return $this;
    }

    public function getModx()
    {
        return $this->_modx;
    }

    public function setModx($modx): ModxResource
    {
        $this->_modx = $modx;
        return $this;
    }
}
