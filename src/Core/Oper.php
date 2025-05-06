<?php

namespace Comba\Core;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Twig\Environment;

/**
 * Oper basic class
 *
 * @category    PHP
 * @package     CombaCart
 */
class Oper extends Options
{

    public ?string $out = null;

    protected $action;
    protected $data;

    private ?Parser $_parser = null;

    /**
     * __construct
     *
     * @param Parser|null $parser class
     *
     */
    public function __construct(?object $parent = null, ?Parser $parser = null)
    {
        parent::__construct($parent);

        $parser = $parser ?? new Parser();

        $this->setParser($parser);
        $this->action = $this->setAction();

        if ($this->getParser()) {
            // додавання шляхів до шаблонів
            $this->addPath();
        }
    }

    /**
     * Return Parser class
     *
     */
    public function getParser(): ?Parser
    {
        return $this->_parser;
    }

    /**
     * Set Parser class
     *
     * @param Parser|null $parser
     * @return Oper
     */
    public function setParser(?Parser $parser): Oper
    {
        if (!empty($parser)) {
            $this->_parser = $parser;
        }

        return $this;
    }

    /**
     * Встановлює шлях до шаблонів класу та встановлює шаблон за замовчуванням
     *
     * @return Oper
     */
    public function addPath(): Oper
    {
        $helloReflection = new ReflectionClass($this);
        if (file_exists($path = dirname($helloReflection->getFilename()) . '/templates')) {
            $this->addPathLoader($path);
        }

        $class = get_class($this);
        $this->getParser()->setTemplateFilename(strtolower(str_replace('Oper', '', $class)));
        return $this;
    }

    /**
     * Додає шлях до шаблонів
     *
     * @param string $path just path
     *
     * @return Oper
     */
    public function addPathLoader(string $path): Oper
    {
        $this->getParser()->getLoader()->addPath($path);
        return $this;
    }

    /**
     * Set global var to Parser
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
     * Закріплює визначені action за класом
     *
     * @return string|null
     */
    public function setAction(): ?string
    {
        return null;
    }

    /**
     * Формує перелік action зі знайдених класів
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
     * Prepare for Render basic method
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
     * Call render() twig
     *
     * @param array $context parsers arguments
     *
     * @return string
     */
    public function renderParser(array $context = []): string
    {
        return
            $this->setTemplates()
                ->parser()
                ->render($this->getParser()->FilenamePath(), $context);
    }

    /**
     * Return parser engine
     *
     */
    public function parser(): Environment
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
     * @return mixed
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
     * Set data from POST raw
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
    public function setFormData(array $data): Oper
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @deprecated This method is obsolete.
     */
    public function getTemplateDirname()
    {
        return $this->parser()->GetTemplateDirname();
    }

    /**
     * @deprecated This method is obsolete.
     */
    public function setTemplateDirname(string $path)
    {
        $this->getParser()->setTemplateDirname($path);
    }

    /**
     * Return current templates type by action
     *
     * @return mixed
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
    public function setTemplateFilename(string $name): Oper
    {
        $this->getParser()->setTemplateFilename($name);
        return $this;
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
            (new Logs('error'))->log('ERROR', $out);
        }
        return $out;
    }

}
