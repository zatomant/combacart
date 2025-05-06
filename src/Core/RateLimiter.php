<?php

namespace Comba\Core;

class RateLimiter extends Cache
{
    protected int $lifetime = 60 * 60;

    protected array $protectionRules = [
        'default' => ['limit' => 5, 'window' => 60, 'block' => 600, 'slowDownFactor' => 1.5],
    ];

    public function __construct($filename = null, bool $memcached = false)
    {
        parent::__construct($filename, $memcached);
    }

    public function getCachePrefix(): string
    {
        return 'RateLimiter';
    }

    /** Відправляє header та блокує викнання коду та записує в лог текст повідомлення
     * @param string $message
     * @param string|null $http_code '400 Bad Request'
     * @param string|null $log
     * @return void
     */
    public function handleError(string $message, ?string $http_code = null, string $log = null): void
    {
        if ($log && method_exists($this, 'log')) {
            $this->log('ALERT', $log);
        }

        header('HTTP/1.1 ' . $http_code ?? '400 Bad Request');
        die('{"result":"error","message":"' . $message . '"}');
    }

    /** Блокує викнання коду та записує в лог текст повідомлення
     * @param string $message
     * @param int $http_code
     * @param string|null $log
     * @return void
     */
    public function handleBlock(string $message, int $http_code = null, string $log = null): void
    {
        if ($log && method_exists($this, 'log')) {
            $this->log('ALERT', $log);
        }

        if ($http_code){
            http_response_code($http_code);
        }
        die($message);
    }

    public function setRules(array $rules): self
    {
        $this->protectionRules = $rules;
        return $this;
    }

    public function registerStatusAttempt(string $identifier, bool $success, string $type = 'default'): self
    {
        $key = strtoupper($type) . '_' . md5($identifier);
        $currentTime = time();
        $this->setFilename($key);

        $data = json_decode($this->get(), true) ?? [
                'attempts' => [],
                'blocked_until' => null,
                $type => $identifier
            ];

        if ($success) {
            $this->delete($this->getFilename());
            return $this;
        }

        if (!empty($data['blocked_until']) && $currentTime < $data['blocked_until']) {
            return $this;
        }

        $rule = $this->protectionRules[$type] ?? $this->protectionRules['default'];

        // Очищаємо старі спроби поза вікном
        $data['attempts'] = array_filter($data['attempts'], function ($timestamp) use ($currentTime, $rule) {
            return $timestamp >= ($currentTime - $rule['window']);
        });

        // Додаємо нову спробу
        $data['attempts'][] = $currentTime;

        // Якщо перевищено ліміт — блокуємо
        if (count($data['attempts']) > $rule['limit']) {
            $data['blocked_until'] = $currentTime + $rule['block'];
            $data['attempts'] = []; // скидаємо спроби після блокування
        }

        if (count($data['attempts']) > 1) {
            $delay = min(count($data['attempts']) * $rule['slowDownFactor'], 5); // Максимум 5 секунд
            usleep($delay * 1000000); // Затримка у мікросекундах
        }

        $this->set(json_encode($data));

        return $this;
    }

    public function isBlocked(string $identifier, string $type = 'default'): bool
    {
        $key = strtoupper($type) . '_' . md5($identifier);
        $this->setFilename($key);
        $data = json_decode($this->get(), true);

        if (empty($data)) {
            return false;
        }

        return !empty($data['blocked_until']) && time() < $data['blocked_until'];
    }

    public function items(string $name, bool $forcename = false): ?array
    {
        return parent::items($this->getFilename() . '*', true);
    }

}
