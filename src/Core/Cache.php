<?php

namespace Comba\Core;

use Psr\Log\LoggerInterface;

class Cache
{

    protected int $lifetime = 30;
    private ?LoggerInterface $logger = null;
    public $_storage;

    public function __construct($filename = null, bool $memcached = false)
    {
        $filename = $filename ?: ($this->getCallerClass() ?: 'cache');

        $callerObject = $this->getCallerObject();

        if ($callerObject && method_exists($callerObject, 'getLogger')) {
            $this->setLogger($callerObject->getLogger());
            $this->setLogLevel($callerObject->getLogLevel());
        } else {
            $this->logger = new Logs();
        }

        if ($memcached) {
            if (class_exists('Memcached')) {
                $this->_storage = new MemcachedStorage($filename);
                $memcached = (bool)$this->_storage->getStats();
            }
        }

        if (!$memcached) {
            $this->_storage = new FileStorage($filename);
        }

        $this->_storage->setCachePrefix($this->getCachePrefix());
    }

    protected function getCallerObject(): ?object
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);

        foreach ([2, 1] as $i) {
            if (isset($backtrace[$i]['object']) && is_object($backtrace[$i]['object'])) {
                return $backtrace[$i]['object'];
            }
        }

        return null;
    }

    public function getCallerClass(): ?string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        for ($i = 1; $i < count($trace); $i++) {
            if (isset($trace[$i]['class'])) {
                // Пропускаємо виклики всередині того ж класу
                if ($trace[$i]['class'] !== ($trace[$i - 1]['class'] ?? null)) {
                    return $trace[$i]['class'];
                }
            }
        }

        return null;
    }

    public function getCachePrefix(): string
    {
        return date('y');
    }

    public function getSuffix(): string
    {
        return $this->_storage->getSuffix();
    }

    public function setFilename(string $name): self
    {
        $this->_storage->setFilename($name);
        return $this;
    }

    public function getFilename(): string
    {
        return $this->_storage->getFilename();
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        if ($logger instanceof Logs) {
            $logger->setLogLevel(Entity::get('LOG_LEVEL_COMBA'));
        }
        return $this;
    }

    public function setLifetime(int $seconds): self
    {
        $this->lifetime = $seconds > 0 ? $seconds : $this->lifetime;
        return $this;
    }

    public function get(): ?string
    {
        $data = $this->_storage->get();
        if (!$data) {
            return null;
        }

        $data = json_decode($data, true);
        $lifetime = (int)trim($data['lifetime']);

        if ($lifetime !== 0 && $lifetime < time()) {
            $this->log('NOTICE', 'Закінчився термін дії кешу ' . $this->_storage->getFullPath() );
            $this->_storage->delete();
            return null;
        }
        return json_encode($data['dataset']) ?: null;
    }

    /** log за PSR-3
     * @param $level
     * @param $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = array()): void
    {
        if ($this->logger instanceof Logs) {
            $level = !isset($level) ? LOG_INFO : (is_int($level) ? $level : $this->logger->mapPsrToSyslog($level));
            $this->logger->put($message, $level, $context);
        } elseif ($this->logger) {
            $level = isset($level) || is_string($level) ? $level : 'INFO';
            $this->logger->log($level, (string)$message, $context);
        }
    }

    public function getLogLevel(): int
    {
        return ($this->logger instanceof Logs)
            ? $this->logger->getLogLevel()
            : Entity::get('LOG_LEVEL_COMBA');
    }

    /**
     * @param int $level LOG_DEBUG, LOG_ERR, LOG_INFO
     * @return $this
     */
    public function setLogLevel(int $level): self
    {
        if ($this->logger instanceof Logs) {
            $this->logger->setLogLevel($level);
        }
        return $this;
    }

    public function delete(string $path = null): bool
    {
        //$path = $path ?? $this->_storage->getFullPath();

        if (empty($path)) {
            return false;
        }

        $deleted = false;

        if ($items = $this->items($path)) {
            foreach ($items as $item) {
                if ($deleted = $this->_storage->delete($item)) {
                    $this->log('NOTICE', "Кеш '$item' успішно видалено");
                } else {
                    $this->log('CRITICAL',"Не вдалося видалити кеш '$item'.");
                }
            }
        }
        return $deleted;
    }

    public function items(string $name, bool $forcename = false): ?array
    {
        return $this->_storage->items($name, $forcename);
    }

    public function set(string $data): self
    {
        $this->log('NOTICE', 'Тривалість кешу ' . $this->lifetime);

        $data = '{"lifetime":' . (time() + $this->lifetime) . ',' . '"dataset":' . $data . '}';

        $this->_storage->set($data);
        return $this;
    }
}
