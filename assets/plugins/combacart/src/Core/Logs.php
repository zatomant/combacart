<?php

namespace Comba\Core;

/**
 * Save log to text file
 */
class Logs extends MyFile
{

    private array $_extendedData;
    private bool $_bClearFile = false;

    function __construct($filename = NULL, $path = NULL)
    {
        $filename = $filename ?: ($this->get_calling_class() ?: 'Logs');
        //$filename = $filename ?: (get_class($this) ?: 'logs');

        parent::__construct($filename, $path);
        $this->setSuffix('.txt');
        $this->setFlag('a');
    }

    public function setExtendedData(string $data, string $key = null): Logs
    {
        $key = $key ?? 'ip';
        $this->_extendedData[$key] = $data;
        return $this;
    }

    /** Set this for clear log
     *
     * @return $this
     */
    function clearfile(): Logs
    {
        $this->_bClearFile = true;
        return $this;
    }

    /**
     * Put text into file
     * @param $params array|string
     * @return $this
     */
    function save($params, $writeDate = true)
    {
        if ($params) {
            if ($this->_bClearFile) $this->setFlag('w');

            if (is_array($params)) {
                foreach ($params as $key => $value) {
                    if (is_array($value)) {
                        $this->save($key . '=>')->save($value, false);
                    } else $this->save($key . '->' . $value, $writeDate);
                }
            } else {
                $params = $writeDate && !empty($this->_extendedData) ? implode(' ', $this->_extendedData) . ' ' . $params : $params;
                $params = $writeDate ? date('Y-m-d H:i:s') . " " . $params : $params;
                $params .= "\n";
                $this->set($params);
            }
        }
        return $this;
    }

    function getFullPath(): string
    {
        return !empty($this->getFilename()) ? $this->getPath() . $this->getFilename() . '-' . date('Ymd') . $this->getSecret() . $this->getSuffix() : $this->getPath() . date('Ymd') . $this->getSecret() . $this->getSuffix();
    }

}