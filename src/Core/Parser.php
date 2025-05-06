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

    public string $templatesPath = '';
    public string $templatesRoot = '';

    private Environment $_engine;
    private FilesystemLoader $_loader;

    /**
     * @deprecated Since version 2.6.34 Will be removed in version 2.6.4
     */
    private string $_templateDirname = '';  // deprecated

    private string $_templateFilename = 'index';
    private string $_templateFilenameExtension = '.html.twig';

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->templatesPath = Entity::get('PATH_TEMPLATES');
        $this->templatesRoot = dirname(__FILE__, 3);

        require_once dirname(__FILE__, 3) . '/vendor/autoload.php';

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

        CombaComponentTwig::register($this->_engine, dirname(__FILE__, 3) . '/assets/components/Twig/');

        $this->addGlobal('btnclass', 'btn-outline-success');
        //$this->addGlobal('btnsize', 'btn-sm');
        $this->addGlobal('formcsize', 'form-control-sm');

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
     * Add extension to parser
     *
     * @param Twig\Extension\ExtensionInterface $a class name
     *
     * @return null
     */
    public function addExtension(Twig\Extension\ExtensionInterface $a)
    {
        $this->_engine->addExtension($a);
    }

    /**
     * Add global var to parser
     *
     * @param string $k key
     * @param mixed $v value
     *
     * @return null
     */
    public function addGlobal(string $k, $v)
    {
        $this->_engine->addGlobal($k, $v);
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
     * Return current parser loader
     *
     * @return FilesystemLoader
     */
    public function getLoader(): FilesystemLoader
    {
        return $this->_loader;
    }

    /**
     * Return parser
     *
     * @return Environment
     */
    public function getEngine(): Environment
    {
        return $this->_engine;
    }

    /**
     * Return full path
     *
     * @return string
     */
    public function filenamePath(): string
    {
        return DIRECTORY_SEPARATOR . $this->getTemplateFilename() . $this->getTemplateFilenameExtension();
    }

    /**
     * @deprecated This method is obsolete.
     */
    public function getTemplateDirname(): string
    {
        return $this->_templateDirname;
    }

    /**
     * @deprecated This method is obsolete.
     */
    public function setTemplateDirname(string $path)
    {
        $this->_templateDirname = $path;
    }

    /**
     * Return filename
     *
     * @return string
     */
    public function getTemplateFilename(): string
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
    public function setTemplateFilename(string $name)
    {
        $this->_templateFilename = $name;
    }

    public function getTemplateFilenameExtension(): string
    {
        return $this->_templateFilenameExtension;
    }

}
