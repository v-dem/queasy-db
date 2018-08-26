<?php

namespace queasy\db;

use PDO;
use ArrayAccess;

use queasy\config\ConfigInterface;
use queasy\config\ConfigAwareTrait;

class Table implements ArrayAccess
{
    use ConfigAwareTrait;

    private $db;

    private $name;

    private $fields;

    public function __construct(PDO $db, $name)
    {
        $this->db = $db;
        $this->name = $name;
        $this->fields = array();
    }

    public function __get($fieldName)
    {
        return $this[$fieldName];
    }

    public function offsetExists($offset)
    {
        return true;
    }

    public function offsetGet($offset)
    {
        if (!isset($this->fields[$offset])) {
            $this->fields[$offset] = new Field($this->name, $offset);
        }

        return $this->fields[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($value)) {
            throw new DbException('Cannot assign null to table field.');
        } elseif (is_array($value)) { // Insert
            // TODO: Implement insert
        } else {
            throw new DbException('Invalid assignment type (must be array).');
        }
    }

    public function offsetUnset($offset)
    {
        throw new Exception('Cannot unset table field.');
    }

    /**
     * Calls an user-defined (in configuration) method
     *
     * @param string $method Method name
     * @param array $args Arguments
     *
     * @return mixed Return type depends on configuration. It can be a single value, an object, an array, or an array of objects or arrays
     *
     * @throws DbException On error
     */
    public function __call($method, array $args)
    {
        if (isset($this->config[$method])) {
            $query = $this->config[$method]['query'];

            $db = $this->db;

            $db->execute(array_merge(array($query), $args));
        } else {
            throw DbException::tableMethodNotImplemented($this->name, $method));
        }
    }
}

