<?php

namespace Comba\Core;

class MyFile
{

    protected string $_path = '';
    private int $_wFlag = 0;
    private string $_filename = 'myfile.conf';
    private string $_suffix = '';
    private string $_secret = '';

    function __construct($filename = null, $path = null)
    {
        if (empty($path)) {
            $path = dirname(__DIR__, 2) . '/logs/';
        }

        $this->setSecret(self::resolveSecret())
            ->setPath($path);

        if (!empty($filename)) {
            $this->setFilename($filename);
        }
    }

    /** Шукає секрет в Entity та env, інакше сформує автоматично
     * @return string
     */
    private static function resolveSecret(): string
    {
        // шукаємо в змінних конфігурації
        if (!empty($entitySecret = Entity::getProtected('MYFILE_SECRET'))) {
            return (string)$entitySecret;
        }

        // повертаємо з .env файлів
        if (!empty($envSecret = getenv('MYFILE_SECRET'))) {
            return substr(trim($envSecret), 0, 32); // обрізаємо до 32 символів
        }

        // або формуємо автоматично
        return substr(hash('sha256', __DIR__ . php_uname()), 0, 32);
    }

    public function delete(string $path = null): bool
    {
        $path = $path ?? $this->getFullPath();
        return file_exists($path) && unlink($path);
    }

    public function getFullPath(): string
    {
        return $this->getPath() . $this->getFilename() . $this->getSecret() . $this->getSuffix();
    }

    public function getPath(): string
    {
        return $this->_path;
    }

    public function setPath(string $path): MyFile
    {
        $this->_path = $this->sanitizeFilename($path, true);
        return $this;
    }

    public function getFilename(): string
    {
        return $this->_filename;
    }

    public function setFilename(string $filename): MyFile
    {
        $this->_filename = $this->sanitizeFilename($filename);
        return $this;
    }

    protected function getSecret(): string
    {
        return empty($this->_secret) ? '' : '_' . hash_hmac('ripemd160', $this->getFilename(), $this->_secret);
    }

    public function setSecret(string $key = ''): MyFile
    {
        $this->_secret = $key;
        return $this;
    }

    public function getSuffix(): string
    {
        return $this->_suffix;
    }

    /**
     * @param $suffix string|null
     * @return $this
     */
    public function setSuffix(?string $suffix): MyFile
    {
        $this->_suffix = $suffix;
        return $this;
    }

    /**
     * @return null|string
     */
    public function get(): ?string
    {
        $filename = $this->getFullPath();
        return file_exists($filename) ? file_get_contents($filename) : null;
    }

    public function set($data): MyFile
    {
        $this->mkdirSafe();

        $filename = $this->getFullPath();
        file_put_contents($filename, $data, $this->getFlag() | LOCK_EX);

        return $this;
    }

    public function mkdirSafe(): MyFile
    {
        $path = $this->getPath();

        if (preg_match('/[^\w\s\-\/\\\]/', $path)) {
            // шлях на директорію "небезпечний"
            return $this;
        }

        // Перевірка, чи є права на запис в батьківську директорію
        if (!is_writable(dirname($path))) {
            // Немає прав на запис у батьківську директорію!
            return $this;
        }

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            chmod($path, 0755);
        }
        return $this;
    }

    public function getFlag(): int
    {
        return $this->_wFlag;
    }

    public function setFlag(int $flag): MyFile
    {
        $this->_wFlag = $flag;
        return $this;
    }

    public function getCallerClass(): ?string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        for ($i = 1; $i < count($trace); $i++) {
            if (isset($trace[$i]['class'])) {
                // Пропускаємо виклики всередині того ж класу
                if ($trace[$i]['class'] !== ($trace[$i-1]['class'] ?? null)) {
                    return $trace[$i]['class'];
                }
            }
        }

        return null;
    }

    public function sanitizeFilename(string $str, ?bool $isPath = false): string
    {
        if (!empty($isPath)) {
            $str = preg_replace(
                '~
        [<>".|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
        [#\[\]@!$&\'()+,;=]|     # URI reserved https://www.rfc-editor.org/rfc/rfc3986#section-2.2
        [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
        ~x', '', $str);
        } else {
            $str = preg_replace("/[^\w\s\d\-_~,;\[\]\(\).]/u", '', $str);
        }

        $str = preg_replace("/[\.]{2,}/u", '', $str);

        return trim($str);
    }
}
