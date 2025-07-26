<?php

namespace queasy\db\query;

use queasy\db\Db;

abstract class TableQuery extends Query
{
    private $tableName;

    public function __construct(Db $db, $tableName, $sql = '')
    {
        $this->tableName = $tableName;

        parent::__construct($db, $sql);
    }

    protected function tableName()
    {
        return $this->tableName;
    }
}

