<?php

namespace queasy\db;

use PDO;
use ArrayAccess;

class Field implements ArrayAccess
{
    private $db;

    private $tableName;

    private $name;

    public function __construct(PDO $db, $tableName, $name)
    {
        $this->db = $db;
        $this->tableName = $tableName;
        $this->name = $name;
    }

    public function offsetExists($offset)
    {
        // echo 'SELECT count(*) FROM `' . $this->tableName . '` WHERE `' . $this->name . '` = ' . $offset . PHP_EOL;
    }

    public function offsetGet($offset)
    {
        if (is_array($offset)) {
            // SELECT * FROM $this->tableName WHERE $this->name IN (...)
        } else {
            // echo 'SELECT * FROM `' . $this->tableName . '` WHERE `' . $this->name . (is_null($offset)? '\' IS NULL': '` = \'' . $offset . '\'') . PHP_EOL;
        }
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($value)) { // Delete
            unset($this[$offset]);
        } elseif (is_array($offset)) {
            // UPDATE ... WHERE $this->name IN (...)
        } else { // Update
            // echo 'UDPATE `' . $this->tableName . '` SET `' . $this->name . '` = \'' . $value . '\' WHERE `' . $this->name . (is_null($offset)? '\' IS NULL': '` = \'' . $offset . '\'') . PHP_EOL;
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

