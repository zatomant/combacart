<?php

namespace Comba\Core;

use function Comba\Functions\sanitizeID;

/**
 * Base class for options
 */
class Options
{
    public array $lang;

    private array $_options = array();
    private bool $_debug = false;

    /**
     * __construct
     *
     * @param string|int $id Primary key
     *
     * @return void
     */
    public function __construct($id = null)
    {
        if (!empty($id)) {
            $this->setOptions('id', $id);
        }
    }

    /** Ініціалізація та заватаження перекладу
     * @return $this
     */
    public function initLang(): Options
    {
        $language = Entity::LANGUAGE;

        $language_file = dirname(__FILE__, 3) . '/assets/lang/' . $language . '.php';
        if (file_exists($language_file)) {

            // мова за-замовчуванням
            $this->lang = include($language_file);

            // визначаемо мову "кліента"
            $_lang = $this->detectLanguage();
            if (!empty($_lang) && ctype_alpha($_lang)) {
                $language_file = dirname(__FILE__, 3) . '/assets/lang/' . $_lang . '.php';
                if (file_exists($language_file)) {
                    $this->lang = array_merge($this->lang, include($language_file));
                }
            }
        }
        return $this;
    }

    /** Визначення мови в рядку запиту
     * @return string|null
     */
    public function detectLanguage(): ?string
    {
        $url = getenv('REQUEST_URI');
        if (substr($url, 0, 1) === '/' && substr($url, 3, 1) == '/') {
            $lang = substr($url, 1, 2);
            if (!empty($lang) && ctype_alpha($lang)) {
                $this->setOptions('language', sanitizeID($lang));
            }
        }
        return $this->getOptions('language');
    }

    /**
     * Getter
     *
     * @param array|string $param vkey
     * @param string|null $default optionaly
     *
     * @return mixed
     */
    public function getOptions($param, string $default = null)
    {
        if ($this->_debug) {
            (new Logs())->save('get ' . $param . '->' . $this->_options[$param]);
        }
        return $this->isExists($param) ? $this->_options[$param] : $default;
    }

    /**
     * Setter
     *
     * @param array|string $param key
     * @param array|string $value value or null for unset key
     *
     * @return $this
     */
    public function setOptions($param, $value = null): Options
    {
        if (!empty($param)) {

            if (is_array($param)) {
                foreach ($param as $key => $val) {
                    $this->setOptions($key, $val);
                }
                return $this;
            }

            if (is_array($value)) {
                $this->_options[$param] = $value;
                if ($this->_debug) {
                    $lg = new Logs();
                    $lg->save('set ' . $param . '->' . implode(';', $value));
                }
            } else {
                if (isset($value)) {
                    if ($this->_debug) {
                        $lg = new Logs();
                        $lg->save('set ' . $param . '->' . $value);
                    }
                    $this->_options[$param] = $value;
                }
            }
        }
        return $this;
    }

    /**
     * Check is exists param
     *
     * @param string $param key
     *
     * @return bool
     */
    public function isExists(string $param): bool
    {
        return array_key_exists($param, $this->_options);
    }

    /** Повертає масив перекладу
     * @return array
     */
    public function getLang(): array
    {
        return $this->lang;
    }

    /**
     * Alias function for options 'lasterror'
     */
    public function getLastError()
    {
        return $this->getOptions('lasterror', false);
    }

    /**
     * Повертає назву та версію браузера що робить запити до сайту
     * або назву за замовчуванням, якщо не визначено браузер
     */
    public function getAgent(string $default = 'bot'): string
    {
        $browser = new \WhichBrowser\Parser(getallheaders());
        if (!empty($browser)) {
            $default = $browser->toString();
        }

        return $default;
    }

    /** Визначає хто робить запит до сайту
     * @param string|null $agent
     * @return bool
     */
    public function isBot(string $agent = null): bool
    {
        return (new \Jaybizzle\CrawlerDetect\CrawlerDetect())->isCrawler($agent);
    }

    /**
     * Dump list options
     *
     * @param bool|null $export optionaly true return array
     * @return array|string
     */
    public function listOptions(bool $export = null)
    {
        if ($export) return $this->_options;
        $out = '';
        foreach ($this->_options as $key => $value) {
            $out .= $key . "=>" . $value . "<br>";
        }
        return $out;
    }

    public function delOptions(string $param)
    {
        if (isset($this->_options[$param])) {
            unset($this->_options[$param]);
        }
    }

    /**
     * Return json string
     *
     * @return string
     */
    public function serialize(): string
    {
        return json_encode($this->_options, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Setter from json string
     *
     * @param string $json input string
     *
     * @return void
     */
    public function unserialize(string $json)
    {
        $isJson = json_decode($json, true);
        if (json_last_error() == JSON_ERROR_NONE && is_array($isJson)) {
            $this->_options = $isJson;
        }
    }
}
