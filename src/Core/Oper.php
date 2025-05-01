<?php

namespace Comba\Core;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * Oper basic class
 *
 * @category    PHP
 * @package     CombaCart
 */
class Oper extends Options
{

    public
        $out;

    protected
        $action,
        $data;

    private
        $_parser;

    /**
     * __construct
     *
     * @param object|null $parser class
     *
     * @return void
     */
    function __construct($parser = null)
    {
        $this->setParser($parser);
        $this->action = $this->setAction();
        if (!empty($this->getParser())) {
            $this->addPath();
        }
    }

    /**
     * Return parser class
     *
     */
    public function getParser()
    {
        return $this->_parser;
    }

    /**
     * Set parser class
     *
     * @param object $parser classname
     *
     * @return void
     */
    public function setParser($parser)
    {
        if (!empty($parser)) {
            $this->_parser = $parser;
        }
    }

    /**
     * Add path basic method
     *
     * @return Oper
     */
    public function addPath(): Oper
    {
        $helloReflection = new ReflectionClass($this);
        $path = dirname($helloReflection->getFilename()) . '/templates';
        if (file_exists($path)) {
            $this->addPathLoader($path);
        }

        $class = get_class($this);
        $this->getParser()->setTemplateFilename(strtolower(str_replace('Oper', '', $class)) . '.html');
        return $this;
    }

    /**
     * Add path to loader for parser
     *
     * @param string $path just path
     *
     * @return Oper
     */
    public function addPathLoader(string $path): Oper
    {
        $this->getParser()->GetLoader()->addPath($path);
        return $this;
    }

    /**
     * Set global var in parser
     *
     * @param string $k key
     * @param mixed $v arguments
     *
     * @return Oper
     */
    public function addGlobal($k, $v): Oper
    {
        $this->getParser()->addGlobal($k, $v);
        return $this;
    }

    /**
     * Get action basic method
     *
     * @return array
     */
    public function getAction(): array
    {
        $act = [];
        if (is_array($this->action)) {
            foreach ($this->action as $action) {
                $act = array_merge(array($action => get_class($this)), $act);
            }
        } else {
            if (!empty($this->action)) {
                $act = array($this->action => get_class($this));
            }
        }

        return $act;
    }

    /**
     * Bind action list
     *
     * @return string|null
     */
    public function setAction(): ?string
    {
        return null;
    }

    /**
     * Get action list from each class file
     *
     * @param string $path path to files
     *
     * @return array
     */
    public function getActionList(string $path): array
    {
        $files = [];
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($rii as $file) {
            if ($file->isDir()) {
                continue;
            }
            if (strpos($file->getBasename(), 'Oper') === false) {
                continue;
            }
            $class = $file->getBasename('.php');
            $files = array_merge((new $class())->getAction(), $files);
        }
        return $files;
    }

    /**
     * Prepare for render basic method
     *
     * @return string|void
     */
    public function render()
    {
        if (!empty($this->getParser())) {
            $this->addPath();
        }
    }

    /**
     * Call render parser
     *
     * @param array $context parsers arguments
     *
     * @return string
     */
    public function renderParser(array $context = []): string
    {
        return $this->setTemplates()
            ->parser()->render($this->getParser()->FilenamePath(), $context);
    }

    /**
     * Return parser engine
     *
     */
    public function parser()
    {
        return $this->getParser()->getEngine();
    }

    /**
     * Set templates file for parser case template`s type
     *
     * @return Oper
     */
    public function setTemplates(): Oper
    {
        return $this;
    }

    /**
     * Get data from GET
     *
     * @param string $key
     * @return string
     */
    public function getData(string $key)
    {
        $value = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        return $value !== null ? $value : $this->getFormData($key);
    }

    /**
     * Get data from data object
     *
     * @param mixed $options key
     *
     * @return mixed
     */
    public function getFormData($options = false)
    {
        return $options ? ($this->data[$options] ?? null) : $this->data;
    }

    /**
     * Set data from post raw
     *
     * @param string $data raw data
     *
     * @return void
     */
    public function setFormDataRaw(string $data)
    {
        $data = base64_decode($data);
        $data = rawurldecode($data);
        parse_str($data, $obj);
        $this->setFormData($obj);
    }

    /**
     * Set data from post param
     *
     * @param array $data options
     *
     * @return Oper
     */
    public function setFormData($data): Oper
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get templates path from parser
     *
     * @return string
     */
    public function getTemplateDirname()
    {
        return $this->parser()->GetTemplateDirname();
    }

    /**
     * Set templates path to parser
     *
     * @param string $path path
     *
     * @return void
     */
    public function setTemplateDirname(string $path)
    {
        $this->getParser()->setTemplateDirname($path);
    }

    /**
     * Return current templates type by action
     *
     * @return string
     */
    public function getTemplatesType()
    {
        return $this->getOptions('templatesType');
    }

    /**
     * Set templates type for advanced action
     *
     * @param string $type name of type
     *
     * @return Oper
     */
    public function setTemplatesType(string $type): Oper
    {
        return $this->setOptions('templatesType', $type);
    }

    /**
     * Return templates filename
     *
     * @return string
     */
    public function getTemplateFilename(): string
    {
        return $this->getParser()->getTemplateFilename();
    }

    /**
     * Set templates filename
     *
     * @param string $name filename
     *
     * @return void
     */
    public function setTemplateFilename(string $name)
    {
        $this->getParser()->setTemplateFilename($name);
    }

    /**
     * Response after parse
     *
     * @param mixed $data optional param
     *
     * @return string|null
     */
    public function response($data = null): ?string
    {
        return isset($data) ? $this->isEmptyResponse($data) : $this->isEmptyResponse($this->out);
    }

    /**
     * Check response answer
     *
     * @param string $out mixed param
     *
     * @return string
     */
    public function isEmptyResponse($out): ?string
    {
        if ($this->getOptions('allowemptydata') == 'true') {
            return $out;
        }

        if (!isset($out) || $out == false) {
            $helloReflection = new ReflectionClass($this);
            $out = $helloReflection->getName();
            $out .= ' -> empty in final parse';
            (new Logs('error'))->save($out);
        }
        return $out;
    }
}
