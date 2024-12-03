<?php

namespace Comba\Core;

use Twig;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extension\StringLoaderExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;

/**
 * Parser
 *
 * @category    PHP
 * @package     CombaCart
 */
class Parser
{

    public string $templatesPath = COMBAMODX_PATH_TEMPLATES;
    public string $templatesRoot = '';

    private
        $_engine,           // parser
        $_loader;

    private string $_templateDirname = '';
    private string $_templateFilename = 'index.html';
    private string $_templateFilenameExtension = '.twig';
    private string $_version = "0.16.3";

    /**
     * __construct
     *
     * @return void
     */
    function __construct()
    {
        $this->templatesRoot = dirname(__FILE__, 3);

        include_once dirname(__FILE__, 3) . '/vendor/autoload.php';

        $this->_loader = new FilesystemLoader($this->templatesPath());
        $this->_engine = new Environment(
            $this->_loader,
            array(
                //'cache' => 'cache',
                'auto_reload' => true,
                // 'debug' => true
            )
        );
        $this->addExtension(new IntlExtension());
        $this->addExtension(new StringLoaderExtension());
        $this->addExtension(new DebugExtension());

        CombaComponentTwig::register($this->_engine, dirname(__FILE__, 3).'/assets/components/Twig/');

        $this->addGlobal('btnclass', 'btn-outline-success');
        //$this->addGlobal('btnsize', 'btn-sm');
        $this->addGlobal('formcsize', 'form-control-sm');

    }

    /**
     * Add extension to parser
     *
     * @param Twig\Extension\ExtensionInterface $a class name
     *
     * @return null
     */
    public function addExtension($a)
    {
        $this->_engine->addExtension($a);
    }

    /**
     * Add filter to parser
     *
     * @param Twig\TwigFilter $a class name
     * @param mixed $b method
     * @param mixed $c arguments
     *
     * @return null
     */
    public function addFilter($a, $b, $c)
    {
        $this->_engine->addFilter($a, $b, $c);
    }

    /**
     * Add global var to parser
     *
     * @param string $k key
     * @param mixed $v value
     *
     * @return null
     */
    public function addGlobal($k, $v)
    {
        $this->_engine->addGlobal($k, $v);
    }

    /**
     * Return current parser loader
     *
     * @return FilesystemLoader
     */
    public function getLoader()
    {
        return $this->_loader;
    }

    /**
     * Return parser
     *
     * @return Environment
     */
    public function getEngine()
    {
        return $this->_engine;
    }

    /**
     * Return templates dir
     *
     * @return string
     */
    function getTemplateDirname(): string
    {
        return $this->_templateDirname;
    }

    /**
     * Set templates dir
     *
     * @param string $path path
     *
     * @return void
     */
    function setTemplateDirname(string $path)
    {
        $this->_templateDirname = $path;
    }

    /**
     * Return filename
     *
     * @return string
     */
    function getTemplateFilename(): string
    {
        return $this->_templateFilename;
    }

    /**
     * Set filename
     *
     * @param string $name filename
     *
     * @return void
     */
    function setTemplateFilename(string $name)
    {
        $this->_templateFilename = $name;
    }

    /**
     * Return full path
     *
     * @return string
     */
    public function filenamePath(): string
    {
        return $this->_templateDirname . DIRECTORY_SEPARATOR . $this->_templateFilename . $this->_templateFilenameExtension;
    }

    /**
     * Return path
     *
     * @return string
     */
    public function templatesPath(): string
    {
        return $this->templatesRoot . DIRECTORY_SEPARATOR . $this->templatesPath;
    }

    /**
     * Return parser version
     *
     * @return string
     */
    public function version(): string
    {
        return $this->_version;
    }
}
