<?php

namespace queasy\db;

use PDO;
use ArrayAccess;

use queasy\db\query\CountQuery;
use queasy\db\query\SelectInQuery;

class Field implements ArrayAccess
{
    private $db;

    private $table;

    private $name;

    public function __construct(PDO $db, Table $table, $name)
    {
        $this->db = $db;
        $this->table = $table;
        $this->name = $name;
    }

    public function offsetExists($offset)
    {
        $query = new CountQuery($this->db, $this->table->name());

        return $query->run(array($this->name => $offset)) > 0;
    }

    public function offsetGet($offset)
    {
        if (is_array($offset)) {
            $query = new SelectInQuery($this->db, $this->table->name(), $this->name);

            return $query->run($offset);
        } else {
            // $query = new GetQuery
            // echo 'SELECT * FROM `' . $this->tableName . '` WHERE `' . $this->name . (is_null($offset)? '\' IS NULL': '` = \'' . $offset . '\'') . PHP_EOL;
        }
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($value)) { // Delete
            unset($this[$offset]);
        } elseif (is_array($offset)) {
            // UPDATE ... WHERE $this->name IN (...)
        } else {
            $query = new TableUpdateQuery($this->db, $this->table->name(), [ $this->name => $offset ]);
            $query->run($value);
        }
    }

    public function offsetUnset($offset)
    {
        if (is_array($offset)) {
            // DELETE FROM ... WHERE $this->name IN (...)
        } else {
            // echo 'DELETE FROM `' . $this->tableName . '` WHERE `' . $this->name . (is_null($offset)? '` IS NULL': '` = \'' . $offset . '\'') . PHP_EOL;
        }
    }
}

