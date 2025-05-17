<?php

namespace Comba\Core;

use Psr\Log\LoggerInterface;
use RemoteIP;
use function Comba\Functions\sanitizeID;

/**
 * Base class for options
 */
class Options
{
    public array $lang;
    protected ?LoggerInterface $logger = null;
    private array $_options = [];
    private int $_log_level = LOG_ERR;

    /**
     * __construct
     *
     * @param string|null $id Primary key
     *
     * @return void
     */
    public function __construct(?object $parent = null, ?string $id = null)
    {

        $this->logger = new Logs(get_class($this));
        $this->_log_level = $parent ? $parent->getLogLevel() : $this->_log_level;
        $this->logger->setLogLevel($this->_log_level);

        if (!empty($id) && !is_object($id) && (!is_string($id) || !class_exists($id))) {
            $this->setOptions('id', $id);
        }
    }

    /**
     * Set class for logging
     *
     * @param string $class
     * @param string $filename
     * @return $this
     */
    public function setLogger(LoggerInterface $logger, string $filename): self
    {
        $this->logger = $logger;
        if ($logger instanceof Logs) {
            $logger->setLogLevel($this->getLogLevel());
        }
        return $this;
    }

    /**
     * instanceof log class
     *
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getLogLevel(): int
    {
        return $this->_log_level;
    }

    /**
     * Встановити тип даних для запису в лог
     * @param int $level LOG_DEBUG, LOG_ERR, LOG_INFO
     * @return $this
     */
    public function setLogLevel(int $level): self
    {
        $this->_log_level = $level;
        if ($this->logger instanceof Logs) {
            $this->logger->setLogLevel($this->_log_level);
        }
        return $this;
    }

    public function setLogFilename(?string $filename)
    {
        if (!empty($filename) && $this->logger instanceof Logs) {
            $this->logger->setFilename($filename);
        }
    }

    /** Ініціалізація та заватаження перекладу
     * @return $this
     */
    public function initLang(): self
    {
        $language = Entity::get('LANGUAGE');

        $language_file = dirname(__FILE__, 3) . '/assets/lang/' . $language . '.php';

        if (file_exists($language_file)) {

            // мова за замовчуванням
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
        $value = $this->_options[$param] ?? [];
        if (is_array($value)){
            $this->log('DEBUG', 'get ' . $param . '->' . (($value !== []) ? var_export($value, true) : ''));
        } else {
            $this->log('DEBUG', 'get ' . $param . '->' . $value);
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
    public function setOptions($param, $value = null): self
    {
        if (empty($param)) {
            return $this;
        }

        if (is_array($param)) {
            foreach ($param as $key => $val) {
                $this->setOptions($key, $val);
            }
            return $this;
        }

        $this->_options[$param] = $value;

        if (is_array($value)) {
            $this->log('DEBUG', 'set ' . $param . '->' . (empty($value) ? '' : var_export($value, true)));
        } elseif (isset($value)) {
            $this->log('DEBUG', 'set ' . $param . '->' . serialize($value));
        }
        return $this;
    }

    /**
     * Запис в лог за PSR-3
     *
     * @param $level
     * @param string|array $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        if (!$this->logger) {
            return;
        }

        $_clsfnc = '';
        if ($this->logger instanceof Logs) {
            $level = is_int($level) ? $level : $this->logger->mapPsrToSyslog($level);
            $this->logger->setLogLevel($this->getLogLevel());
        } else {
            $level = isset($level) || is_string($level) ? $level : 'INFO';
        }

        if ($level == LOG_DEBUG || $level == 'DEBUG') {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            if (isset($backtrace[1])) {
                if (isset($backtrace[1]['class'])) {
                    $_clsfnc .= $backtrace[1]['class'] . $backtrace[1]['type'];
                }
                $_clsfnc .= $backtrace[1]['function'] . "()";
            }
        }

        if ($this->logger instanceof Logs) {

            $this->logger->put($_clsfnc, $level);
            $this->logger->put($message, $level, $context);

        } else {

            $this->logger->log($level, $_clsfnc);
            $this->logger->log($level, (string)$message, $context);

        }
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

    public function getIpAddr(): string
    {
        $ip = (new RemoteIP())->get_ip_address();
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
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
