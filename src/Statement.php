<?php

namespace queasy\db;

use Countable;
use ArrayAccess;
use Iterator;

use PDOStatement;

class Statement extends PDOStatement implements Countable, ArrayAccess, Iterator
{
    private $rowsCache;

    private $rows;

    protected function __construct()
    {
        $this->rowsCache = array();
    }

    public function all()
    {
        if (is_null($this->rows)) {
            $this->rows = $this->rowsCache = call_user_func_array(array($this, 'fetchAll'), func_get_args());
        }

        return $this->rows;
    }

    public function count()
    {
        if (is_null($this->rows)) {
            $this->all();
        }

        return count($this->rows);
    }
}

