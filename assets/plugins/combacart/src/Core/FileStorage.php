<?php

namespace Comba\Core;

class FileStorage extends MyFile
{
    //private string $_path;
    private string $_prefix = '';

    function __construct($filename = null)
    {
        $this->_path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->getPath())) {
            mkdir($this->getPath(), 0755, true);
        }

        $filename = $filename ?: ($this->get_calling_class() ?: 'cache');

        parent::__construct($filename, $this->getPath());

        $this->setSuffix('.json')
            ->setFlag('w');
    }

    public function getPath(): string
    {
        return $this->_path . $this->getCachePrefix() . DIRECTORY_SEPARATOR;
    }

    public function setCachePrefix(string $name): FileStorage
    {
        $this->_prefix = $name;
        return $this;
    }

    public function getCachePrefix(): string
    {
        return $this->_prefix;
    }

    public function items(string $name, bool $forcename = false): ?array
    {
        if (empty($name)) return null;

        $name = basename($name);
        $directory = $this->getPath();
        $name = $forcename ? '/' . $name : '/*_' . $name . '_*';
        $pattern = $directory . $name;
        return glob($pattern);
    }

    public function set($data): FileStorage
    {
        file_put_contents($this->getFullPath(), $data);
        return $this;
    }

}