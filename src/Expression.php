<?php

namespace queasy\db;

class Expression
{
    private $expr;

    public function __construct($expr)
    {
        $this->expr = $expr;
    }

    public function __toString()
    {
        return $this->expr;
    }
}

