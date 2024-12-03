<?php

namespace Comba\Core;

class Cache
{

    protected int $lifetime = 30;
    private $classLog;
    private $_storage;

    private int $_log_level = LOG_ERR;

    function __construct($filename = null, $type = null)
    {
        $this->classLog = null;

        $filename = $filename ?: ($this->get_calling_class() ?: 'cache');

        if ($type == null) {
            $this->_storage = new FileStorage($filename);
        } else {
            $this->_storage = new MemcachedStorage($filename);
        }
        $this->_storage->setCachePrefix($this->getCachePrefix());
    }

    public function getCachePrefix(): string
    {
        return date('y');
    }

    public function getSuffix(): string
    {
        return $this->_storage->getSuffix();
    }

    public function setFilename(string $name): Cache
    {
        $this->_storage->setFilename($name);
        return $this;
    }

    public function get_calling_class()
    {
        //get the trace
        $trace = debug_backtrace();

        // Get the class that is asking for who awoke it
        $class = $trace[1]['class'];

        // +1 to i cos we have to account for calling this function
        for ($i = 1; $i < count($trace); $i++) {
            if (isset($trace[$i])) // is it set?
                if ($class != $trace[$i]['class']) // is it a different class
                    return $trace[$i]['class'];
        }
    }

    public function setLog($class): Cache
    {
        $this->classLog = $class;
        return $this;
    }

    public function setLifetime(int $seconds): Cache
    {
        $this->lifetime = $seconds > 0 ? $seconds : $this->lifetime;
        return $this;
    }

    public function get(): ?string
    {
        $data = $this->_storage->get();
        if (!$data) {
            return null;
        }

        $data = json_decode($data, true);
        $lifetime = (int)trim($data['lifetime']);

        if ($lifetime !== 0 && $lifetime < time()) {
            $this->log('cache expired ' . $this->_storage->getFullPath(), LOG_NOTICE);
            $this->_storage->delete();
            return null;
        }
        return json_encode($data['dataset']) ?: null;
    }

    public function log($data, int $level = LOG_INFO): Cache
    {
        if ($this->getLogLevel() >= $level) {
            if ($this->classLog) {
                $this->classLog->save($data);
            }
        }
        return $this;
    }

    public function getLogLevel(): int
    {
        return $this->_log_level;
    }

    /**
     * @param int $level LOG_DEBUG, LOG_ERR, LOG_INFO
     * @return $this
     */
    public function setLogLevel(int $level): Cache
    {
        $this->_log_level = $level;
        return $this;
    }

    public function delete(string $path = null): bool
    {
        if (empty($path)) {
            return false;
        }

        $ret = false;
        $files = $this->items($path);
        if ($files) {
            foreach ($files as $file) {
                if ($ret = $this->_storage->delete($file)) {
                    $this->log("Кеш '$file' успішно видалено", LOG_NOTICE);
                } else {
                    $this->log("Не вдалося видалити кеш '$file'.", LOG_WARNING);
                }
            }
        }
        return $ret;
    }

    public function items(string $name, bool $forcename = false): ?array
    {
        return $this->_storage->items($name, $forcename);
    }

    public function set($data): Cache
    {
        $this->log('cache lifetime ' . $this->lifetime, LOG_NOTICE);

        $data = '{"lifetime":' . (time() + $this->lifetime) . ',' . '"dataset":' . $data . '}';
        $this->_storage->set($data);
        return $this;
    }
}