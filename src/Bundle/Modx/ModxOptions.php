<?php

namespace Comba\Bundle\Modx;

use Comba\Bundle\CombaApi\CombaApi;
use Comba\Core\Entity;
use Comba\Core\Cache;
use Comba\Core\Options;
use DocumentParser;

class ModxOptions extends Options
{
    protected CombaApi $ca;
    protected bool $isCachable = false;
    protected int $cacheLifetime = 0;
    protected array $noCachableMethod = ['insert', 'update', 'delete', 'new'];

    protected DocumentParser $_modx;

    public function __construct(?object $parent = null, ?DocumentParser $modx = null)
    {
        $this->cacheLifetime = Entity::get('CACHE_LIFETIME');
        parent::__construct($parent);

        if ($modx) {
            $this->setModx($modx);
        } else {
            if ($parent && method_exists(get_class($parent), 'getModx')) {
                $this->setModx($parent->getModx());
            }
        }

        $this->ca = new CombaApi();
    }

    public function setCachable(bool $value = true): ModxOptions
    {
        $this->isCachable = $value;
        return $this;
    }

    /**
     * @param int $value seconds
     * @return $this
     */
    public function setCacheLifeTime(int $value = 0): ModxOptions
    {
        $this->cacheLifetime = $value;
        return $this;
    }

    /**
     * Return instanceof modx
     */
    public function getModx(): DocumentParser
    {
        return $this->_modx;
    }

    /**
     * Prepare modx helper options
     * @param DocumentParser $modx
     * @return $this
     */
    public function setModx(DocumentParser $modx): ModxOptions
    {
        $this->_modx = $modx;
        return $this;
    }

    /**
     * Отримання нового UUID (локально або з Comba серверу)
     * @param bool $serverRequest
     * @param int $length
     * @return string
     */
    public function createUniqueCode(bool $serverRequest = false, int $length = 24): string
    {
        if (!$serverRequest) {
            return $this->guidv4();
        }

        $ret = $this->ca->request('GetNewUID', ['maxlen' => $length]);
        if (!is_array($ret)) {
            $ret = json_decode($ret);
        }
        return !empty($ret->uid) ? $ret->uid : false;
    }

    public function guidv4(string $input = null, string $salt = null): string
    {
        if ($input === null) {
            $data = random_bytes(16);
        } else {

            $salt = $salt ?? sha1(implode('|', [gethostname(), php_uname(), getcwd()]));

            // Хеш з введенням і salt
            $hash = hash('sha256', $salt . '|' . $input, true);
            $data = substr($hash, 0, 16);
        }

        // UUID v4 форматування
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }


    /** get data from remote server if not exsits in cache
     * cacheID = $method + $params[0]
     * return empty string if no ddta or has error
     * @param string $method
     * @param array $params
     * @param string|null $key return by key
     * @param bool $canCached set false for request w\o cache
     * @return string
     */
    public function request(string $method, array $params, string $key = null, bool $canCached = true): string
    {
        $elem = '';
        $key = $key ?: $method;

        if (is_array($params)) {
            $cur = reset($params);
            if (!is_array($cur)) {
                $elem = '_' . $cur;
                //$elem = $cur . '_';
            } else {
                $elem = '_' . current($cur);
                //$elem = current($cur) . '_';
            }
        }

        foreach ($this->noCachableMethod as $word) {
            if (strpos(strtolower($method), strtolower($word)) !== false) {
                $canCached = false;
                break;
            }
        }

        if ($this->isCachable && $canCached) {
            $this->log('INFO', get_class($this) . ' isCachable true');

            $cache = new Cache($method . $elem);

            if ($cacheData = $cache->get()) {
                $this->log('NOTICE', $method . ' отримано з кешу');
                $ret = $cacheData;
            } else {
                $this->log('NOTICE', 'API запит для кешу ' . $method);
                $ret = $this->ca->request($method, $params);
                $_ret = json_decode($ret, true);

                if ($_ret && $_ret['result'] == 'ok') {
                    $this->log('NOTICE', 'Новий кеш для ' . $method);

                    $cache = new Cache($method . $elem);
                    $cache->setLifetime($this->cacheLifetime)
                        //->set(json_encode($__ret[$key]));
                        ->set($ret);
                }
            }
        } else {
            $this->log('INFO', get_class($this) . ' isCachable false');
            $this->log('NOTICE', 'API request() ' . $method);
            $ret = $this->ca->request($method, $params);
        }
        return $ret ?? '';
    }

    /**
     * Видалити дані з локального кешу
     * @param string|null $uid
     * @return $this
     */
    public function invalidateCache(?string $uid): ModxOptions
    {
        if (!empty($uid)) {
            (new Cache())->delete($uid);
        }
        return $this;
    }

    public function setUID(string $uid): ModxOptions
    {
        $this->setOptions('uid', $uid);
        return $this;
    }

    public function getUID(): ?string
    {
        return $this->getOptions('uid');
    }

}
