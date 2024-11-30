<?php

namespace Comba\Core;

class Answer
{

    protected array $options = array();

    function __construct($value, $param = 'status')
    {
        $this->setOptions($value, $param);
    }

    function getOptions($param, $default = NULL)
    {
        return $this->IsExists($param) ? $this->options[$param] : $default;
    }

    function setOptions($value, $param = 'status'): Answer
    {
        if (isset($param) && strlen($param) > 1) {
            if (isset($value) && strlen($value) >= 1) {
                $this->options[$param] = $value;
            } else {
                unset($this->options[$param]);
            }
        }
        return $this;
    }

    function IsExists($param): bool
    {
        return isset($param) && array_key_exists($param, $this->options);
    }

    function listOptions()
    {
        foreach ($this->options as $key => $value) {
            echo $key . '=>' . $value . '<br>';
        }
    }

    function serialize()
    {
        return json_encode($this->options, JSON_UNESCAPED_UNICODE);
    }

    function setOptionsEx($value, $param = 'message'): Answer
    {
        $this->setOptions($value, $param);
        $this->setOptions('result_error', 'status');
        return $this;
    }

}