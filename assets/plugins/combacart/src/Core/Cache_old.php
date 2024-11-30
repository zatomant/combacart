<?php

namespace Comba\Core;

class Cache_old extends MyFile
{
    public $classLog;
    protected int $lifetime = 30;

    //private string $_path;
    private int $_log_level = LOG_ERR;

    function __construct($filename = null, $path = null)
    {
        $this->classLog = null; //new Logs(get_class($this));

        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
        $this->_path = $path . $this->getCachePrefix() . DIRECTORY_SEPARATOR;
        if (!is_dir($this->_path)) {
            mkdir($this->_path, 0755, true);
        }

        $filename = $filename ?: ($this->get_calling_class() ?: 'cache');

        parent::__construct($filename, $this->_path);

        $this->setSuffix('.json')
            ->setFlag('w');
    }

    protected function getCachePrefix(): string
    {
        return date('y');
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
        $data = parent::get();
        if (!$data) {
            return null;
        }

        $data = json_decode($data, true);
        $lifetime = (int)trim($data['lifetime']);

        if ($lifetime !== 0 && $lifetime < time()) {
            $this->log('cache expired ' . $this->getFullPath(), LOG_NOTICE);
            parent::delete();
            return null;
        }
        return json_encode($data['dataset']) ?: null;
    }

    public function delete(string $path = null): bool
    {
        if (empty($path)){
            return false;
        }

        $ret = false;
        $files = $this->items($path);
        if ($files) {
            foreach ($files as $file) {
                if ($ret = parent::delete($file)) {
                    $this->log("Файл кешу '$file' успішно видалено.", LOG_NOTICE);
                } else {
                    $this->log("Не вдалося видалити файл кешу '$file'.", LOG_WARNING);
                }
            }
        }

        return $ret;
    }

    public function items(string $name, bool $forcename = false): ?array
    {
        if (empty($name)) return null;

        $name = basename($name);
        $directory = $this->_path;
        $name = $forcename ? '/' . $name : '/*_' . $name . '_*';
        $pattern = $directory . $name;
        return glob($pattern);
    }

    public function set($data): Cache
    {
        $this->log('cache lifetime ' . $this->lifetime, LOG_NOTICE);

        $data = '{"lifetime":' . (time() + $this->lifetime) . ',' . '"dataset":' . $data . '}';
        file_put_contents($this->getFullPath(), $data);
        return $this;
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
}
