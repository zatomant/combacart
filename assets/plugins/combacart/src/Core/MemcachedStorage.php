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

        $this->_key = $key ?: ($this->get_calling_class() ?: 'cache');
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
        if (empty($name)) return null;

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

        $this->memcached->set($key, $data);
        return $this;
    }

    public function get() {
        $key = $this->getFullPath();
        return $this->memcached->get($key);
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
}