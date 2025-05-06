<?php

namespace Comba\Bundle\Modx;

use Comba\Core\Entity;
use Comba\Core\Oper;
use Comba\Core\Parser;
use DocumentParser;
use function ctype_alpha;

class ModxOper extends Oper
{

    private DocumentParser $_modx;
    private ModxUser $_user;

    public function __construct(?object $parent = null, ?Parser $parser = null)
    {
        $this->detectLanguage();

        parent::__construct($parent, $parser);

        if (!$this->getParser()) {
            return;
        }

        foreach ($this->parser()->getLoader()->getPaths() as $_path) {
            if (!empty($_path)) {
                $path = str_replace(Entity::get('PATH_BUNDLE') . DIRECTORY_SEPARATOR, Entity::get('PATH_CUSTOM') . DIRECTORY_SEPARATOR, $_path);
                if ($path != $_path) {
                    if (file_exists($path)) {
                        $this->parser()->getLoader()->prependPath($path);
                    }
                }
            }
        }
    }

    /**
     * instance ModxUser class
     * @return ModxUser
     */
    public function User(): ModxUser
    {
        return $this->_user;
    }

    /**
     * Повертає пропарсений код
     * @param array $context
     * @return string
     */
    public function renderParser(array $context = []): string
    {
        return
            $this->initLang()
                ->setTemplates()
                ->parser()->render($this->getParser()->filenamePath(), $context);
    }

    /**
     * Додавання перекладу до парсеру
     * use {{ lang.some_phrase }} for Twig
     * use [(__some_phrase)] for Evo
     *
     * @return ModxOper
     */
    public function initLang(): self
    {
        $_options = parent::initLang();
        $_lang = $_options->getLang();

        // для шаблонів Twig
        // масив перекладу
        $this->addGlobal('lang', $_lang);

        $_page = [
            // префікс base url
            'root' => Entity::get('LANGUAGE') != $_options->getOptions('language') && !empty($_options->getOptions('language')) ? $_options->getOptions('language') . '/' : '',
            // поточна мова uk | en | ...
            'language' => $_options->getOptions('language'),

            'singin' => Entity::get('PAGE_SINGIN'),
            'singup' => Entity::get('PAGE_SINGUP'),
            'cabinet' => Entity::get('PAGE_CABINET'),
            'checkout' => Entity::get('PAGE_CHECKOUT'),
            'tnx' => Entity::get('PAGE_TNX')
        ];

        $this->addGlobal('page', $_page);

        // для шаблонів Evo
        foreach ($_lang as $key => $value) {
            $this->getModx()->config['__' . $key] = $value;
        }
        foreach ($_page as $key => $value) {
            $this->getModx()->config['__page_' . $key] = $value;
        }

        return $this;
    }

    /**
     * instanceof modx
     */
    public function getModx(): DocumentParser
    {
        return $this->_modx;
    }

    /**
     * Instanceof modx
     * @param $modx
     * @return ModxOper
     */
    public function setModx(DocumentParser $modx): self
    {
        $this->_modx = $modx;
        $this->_user = new ModxUser($this);
        return $this;
    }

    public function getLanguageList(): ?array
    {
        $langs = [];

        $directory = dirname(__FILE__, 4) . '/assets/lang/';
        $name = '*.php';
        $pattern = $directory . $name;
        $files = glob($pattern);
        foreach ($files as $file) {
            $_lang = include($file);
            $path_parts = pathinfo($file);
            if (Entity::get('LANGUAGE') == $path_parts['filename']) {
                $langs[] = ['url' => $this->getModx()->getConfig('site_url') . Entity::get('PAGE_COMBA'), 'label' => $_lang['_language']];
            } else {
                $langs[] = ['url' => $this->getModx()->getConfig('site_url') . $path_parts['filename'] . '/' . Entity::get('PAGE_COMBA'), 'label' => $_lang['_language']];
            }
        }

        return $langs;
    }

    /**
     * Шукає та повертає чанк з файлу для персера evo|modx
     * ця функція лише для підтримки старого коду
     * для twig ця хрень не потрібна
     */
    public function getChunk(string $tpl, bool $bRecurse = false)
    {
        $template = $tpl;
        if (substr($tpl, 0, 6) == '@FILE:') {

            if (substr($tpl = $this->prepareFilename($tpl), 6, 1) === '/') {
                if (file_exists(substr($tpl, 6))) {
                    $template = file_get_contents(substr($tpl, 6));
                }
            }

            preg_match("!<include>(.*?)</include>!si", $template, $inc);
            if ($inc) {
                foreach ($inc as $el) {
                    $str = $this->getChunk($el, true);
                    $template = str_replace('<include>' . $el . '</include>', $str, $template);
                }
            }
        }
        if (!$bRecurse) {
            $template = '@CODE:' . $template;
        }
        return $template;
    }

    /**
     * Повертає ім'я файлу враховуючи префікс мови в uri
     * @param string $tpl
     * @return string|null
     */
    public function prepareFilename(string $tpl): ?string
    {
        if (empty($tpl)) {
            return null;
        }

        $out = $tpl;

        // це @FILE:/ ?
        if (substr($tpl, 6, 1) === '/') {

            $lang = $this->detectLanguage();
            foreach ($this->parser()->getLoader()->getPaths() as $_path) {

                $_tpl = str_replace('/', $_path . DIRECTORY_SEPARATOR, $tpl);
                if (file_exists(substr($_tpl . $this->getParser()->getTemplateFilenameExtension(), 6))) {
                    $out = $_tpl;
                }
                if (!empty($lang) && ctype_alpha($lang)) {
                    $_tpl_lang = '@FILE:/' . str_replace(
                            '@FILE:/',
                            $_path . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR,
                            $tpl
                        );

                    if (file_exists(substr($_tpl_lang . $this->getParser()->getTemplateFilenameExtension(), 6))) {
                        $out = $_tpl_lang;
                    }
                }
            }
        }

        return $out . $this->getParser()->getTemplateFilenameExtension();
    }

}
