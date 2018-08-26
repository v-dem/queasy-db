<?php

namespace queasy\db;

use ArrayAccess;

class Field implements ArrayAccess
{
    private $name;

    private $tableName;

    public function __construct($name, $tableName)
    {
        $this->name = $name;
        $this->tableName = $tableName;
    }

    public function offsetExists($offset)
    {
        echo 'SELECT count(*) FROM `' . $this->tableName . '` WHERE `' . $this->name . '` = ' . $offset . PHP_EOL;
    }

    public function offsetGet($offset)
    {
        echo 'SELECT * FROM `' . $this->tableName . '` WHERE `' . $this->name . (is_null($offset)? '\' IS NULL': '` = \'' . $offset . '\'') . PHP_EOL;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($value)) { // Delete
            unset($this[$offset]);
        } else { // Update
            echo 'UDPATE `' . $this->tableName . '` SET `' . $this->name . '` = \'' . $value . '\' WHERE `' . $this->name . (is_null($offset)? '\' IS NULL': '` = \'' . $offset . '\'') . PHP_EOL;
        }
    }

    public function offsetUnset($offset)
    {
        echo 'DELETE FROM `' . $this->tableName . '` WHERE `' . $this->name . (is_null($offset)? '` IS NULL': '` = \'' . $offset . '\'') . PHP_EOL;
    }
}

