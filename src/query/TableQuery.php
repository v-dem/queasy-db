<?php

namespace queasy\db\query;

use queasy\db\Db;

abstract class TableQuery extends Query
{
    private $table;

    public function __construct(Db $db, $table, $sql = '')
    {
        $this->table = $table;

        parent::__construct($db, $sql);
    }

    protected function table()
    {
        return $this->table;
    }
}

