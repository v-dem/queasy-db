<?php

namespace queasy\db;

class Expression
{
    private $expression;

    private $bindings;

    public function __construct($expression, array $bindings = array())
    {
        $this->expression = $expression;
        $this->bindings = $bindings;
    }

    public function getExpression()
    {
        return $this->expression;
    }

    public function getBindings()
    {
        return $this->bindings;
    }

    public function __toString()
    {
        return $this->expression;
    }
}

