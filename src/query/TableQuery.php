<?php

namespace queasy\db\query;

use queasy\db\Db;

abstract class TableQuery extends Query
{
    private $tableName;

    public function __construct(Db $pdo, $tableName, $sql = '')
    {
        $this->tableName = $tableName;

        parent::__construct($pdo, $sql);
    }

    protected function tableName()
    {
        return $this->tableName;
    }
}

