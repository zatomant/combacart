<?php

namespace Comba\Core;

use Memcached;

class MemcachedStorage
{
    private Memcached $memcached;
    private string $keyListKey = 'memcached_keys_list';

    private string $_path;
    private string $_key;
    private string $_secret = '';
    private string $_prefix = '';

    public function __construct(string $key) {
        $this->memcached = new Memcached();
        $this->memcached->addServer('localhost', 11211);

        $this->_path = '' ;

        $this->_key = $key ?: ($this->getCallerClass() ?: 'cache');
    }

    public function getStats()
    {
        return $this->memcached->getStats();
    }

    public function setCachePrefix(string $name): MemcachedStorage
    {
        $this->_prefix = $name;
        return $this;
    }

    public function getCachePrefix(): string
    {
        return $this->_prefix;
    }

    public function setFilename(string $filename): MemcachedStorage
    {
        $this->_key = $filename;
        return $this;
    }

    public function getFilename(): string
    {
        return $this->_key;
    }

    protected function getSecret(): string
    {
        return empty($this->_secret) ? '' : '_' . hash_hmac('ripemd160', $this->getFilename(), $this->_secret);
    }

    public function setSecret(string $key = ''): MemcachedStorage
    {
        $this->_secret = $key;
        return $this;
    }

    public function getPath(): string
    {
        return $this->_path;
    }

    public function setPath(string $path): MemcachedStorage
    {
        $this->_path = $path;
        return $this;
    }

    public function getFullPath(): string
    {
        return  $this->getPath() . $this->getFilename() . $this->getSecret() ;
    }

    private function getKeysByPattern($pattern) {
        $keyList = $this->memcached->get($this->keyListKey) ?: [];
        return array_filter($keyList, function($key) use ($pattern) {
            return fnmatch($pattern, $key); // Використовуємо шаблон для фільтрації
        });
    }

    public function items(string $name, bool $forcename = false): ?array
    {
        if (empty($name)) {
            return null;
        }

        $name = basename($name);
        $directory = $this->_path;
        //$name = $forcename ? '/' . $name : '/*_' . $name . '_*';
        $name = $forcename ? $name : '*' . $name . '*';
        $pattern = $directory . $name;
        return $this->getKeysByPattern($pattern);
    }

    public function set($data): MemcachedStorage
    {
        $key = $this->getFullPath();

        // Додаємо ключ у список ключів
        $keyList = $this->memcached->get($this->keyListKey) ?: [];
        if (!in_array($key, $keyList)) {
            $keyList[] = $key;
            $this->memcached->set($this->keyListKey, $keyList);
        }

        $ttl = 0;
        if ($_json = json_decode($data, true)){
            $ttl = $_json['lifetime'] ? $_json['lifetime'] - time() : 0;
        }

        $this->memcached->set($key, $data, $ttl);
        return $this;
    }

    public function get() {
        return $this->memcached->get($this->getFullPath());
    }

    public function delete(string $path = null): bool
    {
        $key = $path ?? $this->getFullPath();

        // Видаляємо ключ зі списку ключів
        $keyList = $this->memcached->get($this->keyListKey) ?: [];
        if (($index = array_search($key, $keyList)) !== false) {
            unset($keyList[$index]);
            $this->memcached->set($this->keyListKey, $keyList);
        }

        return $this->memcached->delete($key);
    }

    public function getCallerClass(): ?string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        for ($i = 1; $i < count($trace); $i++) {
            if (isset($trace[$i]['class'])) {
                // Пропускаємо виклики всередині того ж класу
                if ($trace[$i]['class'] !== ($trace[$i-1]['class'] ?? null)) {
                    return $trace[$i]['class'];
                }
            }
        }

        return null;
    }
}
