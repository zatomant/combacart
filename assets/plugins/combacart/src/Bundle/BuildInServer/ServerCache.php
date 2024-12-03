<?php

namespace Comba\Bundle\BuildInServer;

use Comba\Core\Cache;

class ServerCache extends Cache
{
    protected int $lifetime = 63072000; // 2 роки

    public function getCachePrefix(): string
    {
        return 'Server';
    }
}