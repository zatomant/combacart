<?php

namespace Comba\Core;

class MyFile
{

    private string $_wFlag = 'w';
    private string $_filename = 'myfile.conf';
    protected string $_path = '';
    private string $_suffix = '';
    private string $_secret = '';

    function __construct($filename = null, $path = null)
    {
        if (empty($path)) {
            $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
        }

        $this->setSecret('FFFFFF')->setPath($path);

        if (!empty($filename)) {
            $this->setFilename($filename);
        }
    }

    private function sanitizeFilename(string $str, ?bool $isPath = false): string
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
        } else $str = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $str);

        $str = mb_ereg_replace("([\.]{2,})", '', $str);

        return trim($str);
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
        $filename = $this->getFullPath();
        $f = fopen($filename, $this->_wFlag);
        if ($f) {
            flock($f, 2);
            fwrite($f, $data);
            flock($f, 3);
            fclose($f);
        }
        return $this;
    }

    public function getFlag(): string
    {
        return $this->_wFlag;
    }

    public function setFlag($flag): MyFile
    {
        $this->_wFlag = $flag;
        return $this;
    }

    public function get_calling_class()
    {
        //get the trace
        $trace = debug_backtrace();

        // Get the class that is asking for who awoke it
        $class = $trace[1]['class'];

        // +1 to i cos we have to account for calling this function
        for ($i = 1; $i < count($trace); $i++) {
            if (isset($trace[$i])) // is it set?
                if ($class != $trace[$i]['class']) // is it a different class
                    return $trace[$i]['class'];
        }
    }
}
