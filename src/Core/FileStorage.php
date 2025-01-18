<?php

namespace Comba\Core;

class FileStorage extends MyFile
{
    private string $_prefix = '';

    function __construct($filename = null)
    {
        $this->_path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
        $this->mkdirSafe();

        $filename = $filename ?: ($this->get_calling_class() ?: 'cache');

        parent::__construct($filename, $this->getPath());

        $this->setSuffix('.json');
    }

    public function getPath(): string
    {
        return $this->_path . (!empty($this->getCachePrefix()) ? $this->getCachePrefix() . DIRECTORY_SEPARATOR : '');
    }

    public function getCachePrefix(): string
    {
        return $this->_prefix;
    }

    public function set($data): FileStorage
    {
        $this->mkdirSafe();

        file_put_contents($this->getFullPath(), $data);
        return $this;
    }

    public function setCachePrefix(string $name): FileStorage
    {
        $this->_prefix = $name;
        return $this;
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


}
