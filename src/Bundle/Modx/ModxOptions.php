<?php

namespace Comba\Bundle\Modx;

use Comba\Bundle\CombaApi\CombaApi;
use Comba\Core\Entity;
use Comba\Core\Cache;
use Comba\Core\Options;

class ModxOptions extends Options
{
    protected CombaApi $ca;
    protected bool $isCachable = false;
    protected int $cacheLifetime = Entity::CACHE_LIFETIME;
    protected array $noChachableMethod = ['insert', 'update', 'delete', 'new'];

    protected $_modx;

    private $_log;
    private string $_log_classname = '\Comba\Core\Logs';
    private int $_log_level = LOG_ERR;

    public function __construct($modx = null)
    {
        $this->setModx($modx);
        $this->ca = new CombaApi();
        $this->_log = new $this->_log_classname(get_class($this));
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
    public function getModx()
    {
        return $this->_modx;
    }

    /**
     * Prepare modx helper options
     * @param $modx
     * @return $this
     */
    public function setModx($modx): ModxOptions
    {
        $this->_modx = $modx;
        return $this;
    }

    /**
     * Отримати UUID з Comba серверу
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

    /** Згенерувати UUID
     * @param $data
     * @return string|void
     * @throws \Exception
     */
    public function guidv4($data = null): string
    {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
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

        foreach ($this->noChachableMethod as $word) {
            if (strpos(strtolower($method), strtolower($word)) !== false) {
                $canCached = false;
                break;
            }
        }

        if ($this->isCachable && $canCached) {
            $this->log(get_class($this) . ' isCachable true', LOG_INFO);

            $cache = new Cache($method . $elem);
            $cache->setLog($this->_log);

            if ($cacheData = $cache->get()) {
                $this->log('get ' . $method . ' from cache', LOG_INFO);
                $ret = $cacheData;
            } else {
                $this->log('cache api request ' . $method, LOG_INFO);
                $ret = $this->ca->request($method, $params);
                $_ret = json_decode($ret, true);

                if ($_ret && $_ret['result'] == 'ok') {
                    $this->log('cache write ' . $method, LOG_INFO);

                    $cache = new Cache($method . $elem);
                    $cache->setLog($this->_log)
                        ->setLifetime($this->cacheLifetime)
                        //->set(json_encode($__ret[$key]));
                        ->set($ret);
                }
            }
        } else {
            $this->log(get_class($this) . ' isCachable false', LOG_INFO);
            $this->log('api request ' . $method, LOG_INFO);
            $ret = $this->ca->request($method, $params);
        }
        return $ret ?? '';
    }

    /**
     * Записати в лог
     *
     * @param string|array $data
     * @param string|null $filename optionaly filename for log`s file
     * @param bool $logforce set true to save log anyway
     * @return $this
     */
    public function log($data, int $level = LOG_INFO, string $filename = null, bool $logforce = false): ModxOptions
    {
        if ($logforce == true || $this->getLogLevel() >= $level) {
            if ($this->_log) {
                if (!empty($filename)) {
                    $this->_log->setFilename($filename);
                }
                $this->_log->save($data);
            }
        }
        return $this;
    }

    public function getLogLevel(): int
    {
        return $this->_log_level;
    }

    /**
     * Встановити тип даних для запису в лог
     * @param int $level LOG_DEBUG, LOG_ERR, LOG_INFO
     * @return $this
     */
    public function setLogLevel(int $level): ModxOptions
    {
        $this->_log_level = $level;
        return $this;
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

    /**
     * Set class for logging
     *
     * @param string $class
     * @param string $filename
     * @return $this
     */
    public function setLog(string $class, string $filename): ModxOptions
    {
        $this->_log = new $class($filename);
        return $this;
    }

    public function setLogFilename(?string $filename)
    {
        if (!empty($filename)) {
            $this->_log->setFilename($filename);
        }
    }
}
