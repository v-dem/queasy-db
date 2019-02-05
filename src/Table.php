<?php

namespace queasy\db;

use PDO;
use ArrayAccess;
use Countable;

use queasy\config\ConfigInterface;
use queasy\config\ConfigAwareTrait;

use queasy\db\query\SingleValueQuery;
use queasy\db\query\SingleInsertQuery;
use queasy\db\query\SingleNamedInsertQuery;
use queasy\db\query\BatchInsertQuery;
use queasy\db\query\BatchNamedInsertQuery;
use queasy\db\query\BatchSeparatelyNamedInsertQuery;

class Table implements ArrayAccess, Countable
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

    public function count()
    {
        $query = new SingleValueQuery($this->db, sprintf('SELECT count(*) FROM `%s`', $this->name));

        return $query->run();
    }

    public function insert()
    {
        // TODO: Be careful!!!
        $params = (1 === func_num_args())? func_get_arg(0): func_get_args();

        if (is_null($params)) {
            throw new DbException('Cannot assign null to table field.');
        } elseif (is_array($params)) {
            $keys = array_keys($params);
            if (count($keys) && is_array($params[$keys[0]])) { // Batch inserts
                if ((2 === count($params))
                        && is_array($params[1])
                        && (0 < count($params[1]))
                        && isset($params[1][0])
                        && is_array($params[1][0])) { // Batch insert with field names listed in a separate array
                    $query = new BatchSeparatelyNamedInsertQuery($this->db, $this->name);
                } else {
                    $keys = array_keys($params[$keys[0]]);

                    $query = (!count($keys) || is_numeric($keys[0]))
                        ? new BatchInsertQuery($this->db, $this->name) // Batch insert
                        : new BatchNamedInsertQuery($this->db, $this->name); // Batch insert with field names
                }
            } else { // Single inserts
                $query = (!count($keys) || is_numeric($keys[0]))
                    ? new SingleInsertQuery($this->db, $this->name) // By order, without field names
                    : new SingleNamedInsertQuery($this->db, $this->name); // By field names
            }
        } else {
            throw new DbException('Invalid assignment type (must be array).');
        }

        return $query->run($params);
    }

    public function offsetExists($offset)
    {
        return true;
    }

    public function offsetGet($offset)
    {
        if (!isset($this->fields[$offset])) {
            $this->fields[$offset] = new Field($this->db, $this->name, $offset);
        }

        return $this->fields[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->insert($value);
        } else {
            throw new DbException('Not implemented. Use Field instead of Table to update record.');
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

            $this->db->execute(array_merge(array($query), $args));
        } else {
            throw DbException::tableMethodNotImplemented($this->name, $method);
        }
    }
}

