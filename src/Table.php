<?php

namespace queasy\db;

use InvalidArgumentException;
use BadMethodCallException;

use PDO;
use PDOException;
use ArrayAccess;
use Countable;
use IteratorAggregate;

use queasy\helper\System;

use queasy\db\query\CustomQuery;
use queasy\db\query\QueryBuilder;

class Table implements ArrayAccess, Countable, IteratorAggregate
{
    protected $db;

    protected $name;

    protected $fields;

    /**
     * Config instance.
     *
     * @var array|ArrayAccess
     */
    protected $config;

    public function __construct(Db $db, $name, $config = array())
    {
        $this->db = $db;
        $this->name = $name;
        $this->fields = array();
        $this->config = $config;
    }

    public function __get($fieldName)
    {
        if (!isset($this->fields[$fieldName])) {
            $field = new Field($this->db, $this, $fieldName);

            $this->fields[$fieldName] = $field;
        }

        return $this->fields[$fieldName];
    }

    public function statement()
    {
        return $this->where()->select();
    }

    public function all()
    {
        return $this->statement()->fetchAll();
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return $this->statement()->getIterator();
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (null !== $offset) {
            if (!($value instanceof QueryBuilder) || !is_array($offset)) {
                throw new BadMethodCallException('Not implemented. Use Field instead of Table to update record.');
            }

            $value
                ->into($this->name)
                ->insert($offset);

            return $this->db->getLastStatement()->rowCount();
        }

        $this->insert($value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Calls an user-defined (in configuration) method
     *
     * @param string $method Method name
     * @param array $args Arguments
     *
     * @return mixed Return type depends on configuration. It can be a single value, a stdClass object, an array, or an array of objects or arrays, or PDOStatement instance
     *
     * @throws DbException On error
     */
    public function __call($method, array $args)
    {
        if (isset($this->config[$method])) {
            $query = new CustomQuery($this->db, $this->config[$method]);

            return System::callUserFuncArray(array($query, 'run'), $args);
        }

        throw new InvalidArgumentException("Method \"$method\" is not declared in \"{$this->name}\" table configuration.");
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        return $this->where()
            ->select([ 'count' => $this->db->expr('count(*)') ])
            ->fetchColumn();
    }

    public function insert()
    {
        $params = (1 === func_num_args())
            ? func_get_arg(0)
            : func_get_args();

        if ((null === $params) || !is_array($params)) {
            throw new InvalidArgumentException('Argument must be a non-empty array.');
        }

        $keys = array_keys($params);

        // Default is single inserts
        $isSingleInsert = true;
        $queryClass = (!count($keys) || is_numeric($keys[0]))
            ? 'queasy\\db\\query\\SingleInsertQuery' // By order, without field names
            : 'queasy\\db\\query\\SingleNamedInsertQuery'; // By field names

        if (count($keys) && is_array($params[$keys[0]])) { // Batch inserts
            $isSingleInsert = false;
            $keys = array_keys($params[$keys[0]]);
            $queryClass = (!count($keys) || is_numeric($keys[0]))
                ? 'queasy\\db\\query\\BatchInsertQuery' // Batch insert
                : 'queasy\\db\\query\\BatchNamedInsertQuery'; // Batch insert with field names
        }

        $query = new $queryClass($this->db, $this->name);

        $statement = $query($params);

        if ($isSingleInsert) {
            try {
                return $this->db->lastInsertId();
            } catch (PDOException $e) { // Driver doesn't support this
                return false;
            }
        }

        return $statement->rowCount();
    }

    public function update(array $params, $fieldName = null, $fieldValue = null, array $options = array())
    {
        $builder = $this->where()->options($options);

        if (is_array($fieldValue)) {
            $inExpr = $this->db->inExpr($fieldName, $fieldValue);

            $builder = $builder->where($inExpr, $inExpr->getBindings());
        } elseif ($fieldName !== null) {
            $builder = $builder->where("\"$fieldName\" = :$fieldName", [
                $fieldName => $fieldValue
            ]);
        }

        return $builder->update($params)->rowCount();
    }

    public function delete($fieldName = null, $fieldValue = null, array $options = array())
    {
        $builder = $this
            ->where("\"$fieldName\" IS NULL")
            ->options($options);

        if (is_array($fieldValue)) {
            $inExpr = $this->db->inExpr($fieldName, $fieldValue);

            $builder = $builder->where($inExpr, $inExpr->getBindings());
        } elseif (null != $fieldValue) {
            $builder = $builder->where("\"$fieldName\" = :$fieldName", [
                $fieldName => $fieldValue
            ]);
        }

        return $builder->delete()->rowCount();
    }

    public function truncate()
    {
        $this->db->query('TRUNCATE TABLE "' . $this->name . '"');
    }

    public function where($where = '', array $bindings = array())
    {
        return (new QueryBuilder($this->db, $this->name))
            ->where($where, $bindings);
    }

    public function getName()
    {
        return $this->name;
    }
}

