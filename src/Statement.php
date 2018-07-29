<?php

namespace queasy\db;

use PDOStatement;

class Statement extends PDOStatement
{
    protected function __construct()
    {
    }

    public function all()
    {
        return call_user_func_array(array($this, 'fetchAll'), func_get_args());
    }
}

