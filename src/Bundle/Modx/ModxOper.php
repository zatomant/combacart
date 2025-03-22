<?php

namespace Comba\Bundle\Modx;

use Comba\Core\Entity;
use Comba\Core\Oper;
use ReflectionClass;
use function ctype_alpha;

class ModxOper extends Oper
{

    private $_modx;
    private ModxUser $_user;

    function __construct($parser = null)
    {
        $this->detectLanguage();
        $this->setParser($parser);
        $this->action = $this->setAction();
        if (!empty($this->getParser())) {
            $this->addPath();
        }
    }

    /**
     * Get CombaModxUser class
     * @return ModxUser
     */
    public function User(): ModxUser
    {
        return $this->_user;
    }

    function renderParser(array $context = []): string
    {
        return $this->initLang()
            ->setTemplates()
            ->parser()
            ->render($this->prepareFilename($this->getParser()->filenamePath()), $context);
    }

    /**
     * Додавання перекладу до парсеру
     * use {{ lang.some_phrase }} for Twig
     * use [(__some_phrase)] for Evo
     *
     * @return ModxOper
     */
    public function initLang(): ModxOper
    {
        $_options = parent::initLang();
        $_lang = $_options->getLang();

        // для шаблонів Evo
        foreach ($_lang as $key => $value) {
            $this->getModx()->config['__' . $key] = $value;
        }

        // для шаблонів Twig
        $this->addGlobal('lang', $_lang); // масив перекладу
        $this->addGlobal('language', $_options->getOptions('language')); // uk | en | ...

        return $this;
    }

    /**
     * Return Instanceof modx
     */
    public function getModx()
    {
        return $this->_modx;
    }

    /**
     * Instanceof modx
     * @param $modx
     * @return ModxOper
     */
    public function setModx($modx): ModxOper
    {
        $this->_modx = $modx;
        $this->_user = new ModxUser($this->getModx());
        return $this;
    }

    function prepareFilename($tpl): ?string
    {
        if (empty($tpl)) return $tpl;

        $sufix = $this->getOptions('language');
        if (!empty($sufix) && ctype_alpha($sufix)) {
            $_tpl = str_replace('.html', '_' . $sufix . '.html', $tpl);
            $helloReflection = new ReflectionClass($this);
            $path = dirname($helloReflection->getFilename()) . '/templates';
            if (file_exists($path . $_tpl)) {
                $tpl = $_tpl;
            }
        }
        return $tpl;
    }

    public function getLanguageList(): ?array
    {
        $langs = array();

        $directory = dirname(__FILE__, 4) . '/assets/lang/';
        $name = '*.php';
        $pattern = $directory . $name;
        $files = glob($pattern);
        foreach ($files as $file) {
            $_lang = include($file);
            $path_parts = pathinfo($file);
            if (Entity::LANGUAGE == $path_parts['filename']) {
                $langs[] = ['url' => $this->getModx()->getConfig('site_url') . Entity::PAGE_COMBA, 'label' => $_lang['_language']];
            } else {
                $langs[] = ['url' => $this->getModx()->getConfig('site_url') . $path_parts['filename'] . '/' . Entity::PAGE_COMBA, 'label' => $_lang['_language']];
            }
        }

        return $langs;
    }
}
