<?php

namespace Comba\Core;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Логування у текстовий файл з підтримкою рівнів логування syslog та PSR-3
 */
class Logs extends MyFile implements LoggerInterface
{
    private const LEVEL_NAMES = [
        LOG_EMERG => 'EMERGENCY',
        LOG_ALERT => 'ALERT',
        LOG_CRIT => 'CRITICAL',
        LOG_ERR => 'ERROR',
        LOG_WARNING => 'WARNING',
        LOG_NOTICE => 'NOTICE',
        LOG_INFO => 'INFO',
        LOG_DEBUG => 'DEBUG'
    ];

    private static array $psrToSyslog = [
        LogLevel::EMERGENCY => LOG_EMERG,
        LogLevel::ALERT     => LOG_ALERT,
        LogLevel::CRITICAL  => LOG_CRIT,
        LogLevel::ERROR     => LOG_ERR,
        LogLevel::WARNING   => LOG_WARNING,
        LogLevel::NOTICE    => LOG_NOTICE,
        LogLevel::INFO      => LOG_INFO,
        LogLevel::DEBUG     => LOG_DEBUG,
    ];

    private array $globalContext = [];
    private bool $clearFile = false;
    private int $logLevel = LOG_ERR;

    public function __construct(?string $filename = null, ?string $path = null)
    {
        $filename = $filename ?: ($this->getCallerClass() ?: 'Logs');
        parent::__construct($filename, $path);
        $this->setSuffix('.txt')
            ->setFlag(FILE_APPEND);
    }

    public function setGlobalContext(array $context): self
    {
        $this->globalContext = $context;
        return $this;
    }

    public function clearFile(): self
    {
        $this->clearFile = true;
        return $this;
    }

    public function getFullPath(): string
    {
        $filename = $this->getFilename();
        $datePart = date('Ymd');

        return $this->getPath() . ($filename ? $filename . '-' : '') . $datePart . $this->getSecret() . $this->getSuffix();
    }

    /** Запис в лог. Залишено для сумісності зі старим кодом.
     * @param $data
     * @param int $level
     * @param array $context
     * @return $this
     */
    public function put($data, int $level = LOG_INFO, array $context = []): self
    {
        if ($level > $this->logLevel || empty($data)) {
            return $this;
        }

        if ($this->clearFile) {
            $this->setFlag(0);
            $this->clearFile = false;
        }

        if (!empty($context) && is_array($data)) {
            $data = array_merge($data, $context);
        }

        $lines = is_array($data) ? $this->flattenArrayToLines($data) : [$data];

        foreach ($lines as $line) {
            $this->writeLine($line, true, $level);
        }

        return $this;
    }

    /** Підготовка масиву до запису в лог
     * @param array $data
     * @param string $prefix
     * @return array
     */
    private function flattenArrayToLines(array $data, string $prefix = ''): array
    {
        $lines = [];

        foreach ($data as $key => $value) {
            $fullKey = $prefix === '' ? $key : "{$prefix}->{$key}";

            if (is_array($value)) {
                $lines = array_merge($lines, $this->flattenArrayToLines($value, $fullKey));
            } else {
                $lines[] = "{$fullKey}->{$value}";
            }
        }

        return $lines;
    }

    /** Форматування та запис строки в лог
     * @param string $data
     * @param bool $writeDate
     * @param int|null $level
     * @return void
     */
    private function writeLine(string $data, bool $writeDate, ?int $level): void
    {
        $message = $writeDate
            ? $this->formatMessage($data, $level)
            : $data;

        $this->set($message . PHP_EOL);
    }

    private function formatMessage(string $data, ?int $level, array $context = []): string
    {
        $parts = [date('Y-m-d H:i:s')];

        if ($level !== null) {
            $parts[] = $this->getLevelName($level);
        }

        if (!empty($context)) {
            $parts[] = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $parts[] = $data;

        return implode(' ', $parts);
    }

    private function getLevelName(int $level): string
    {
        return isset(self::LEVEL_NAMES[$level])
            ? '[' . self::LEVEL_NAMES[$level] . ']'
            : '';
    }

    public function getLogLevel(): int
    {
        return $this->logLevel;
    }

    public function setLogLevel($level): self
    {
        if (is_string($level)) {
            $level = $this->mapPsrToSyslog($level);
        }

        if (!is_int($level)) {
            throw new \InvalidArgumentException("Invalid log level.");
        }

        $this->logLevel = $level;
        return $this;
    }


    // PSR-3

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /** Log за PSR-3
     * @param $level
     * @param $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        $syslogLevel = self::mapPsrToSyslog($level);

        if ($syslogLevel > $this->logLevel) {
            return;
        }

        $mergedContext = array_merge($this->globalContext, $context);

        if (is_string($message)) {
            $message = $this->interpolate($message, $mergedContext);
        }

        $this->put($message, $syslogLevel);
    }

    private function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $val) {
            if (is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replace["{{$key}}"] = $val;
            } else {
                $replace["{{$key}}"] = json_encode($val);
            }
        }

        return strtr($message, $replace);
    }

    public static function mapPsrToSyslog(string $level): int
    {
        return self::$psrToSyslog[strtolower($level)] ?? LOG_ERR;
    }
}
