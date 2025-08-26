<?php

namespace queasy\db;

class Blob
{
    private $value;
    private $length;

    public function __construct($value, $length = null)
    {
        $this->value = $value;
        $this->length = (is_string($value) && (null === $length))
            ? strlen($value)
            : $length;
    }

    public function __toString()
    {
        return $this->value;
    }

    public function length()
    {
        return $this->length;
    }
}

