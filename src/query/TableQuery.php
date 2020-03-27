<?php

namespace queasy\db\query;

use PDO;

abstract class TableQuery extends Query
{
    private $tableName;

    public function __construct(PDO $db, $tableName, $sql = '')
    {
        $this->tableName = $tableName;

        parent::__construct($db, $sql);
    }

    protected function tableName()
    {
        return $this->tableName;
    }
}

